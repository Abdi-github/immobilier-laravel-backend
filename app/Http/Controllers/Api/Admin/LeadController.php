<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Lead\Enums\InquiryType;
use App\Domain\Lead\Enums\LeadPriority;
use App\Domain\Lead\Enums\LeadSource;
use App\Domain\Lead\Enums\LeadStatus;
use App\Domain\Lead\Events\LeadAssigned;
use App\Domain\Lead\Events\LeadClosed;
use App\Domain\Lead\Events\LeadStatusChanged;
use App\Domain\Lead\Models\Lead;
use App\Domain\Lead\Models\LeadNote;
use App\Http\Controllers\Controller;
use App\Http\Resources\LeadNoteResource;
use App\Http\Resources\LeadResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LeadController extends Controller
{
    use ApiResponse;

    private const VALID_TRANSITIONS = [
        'NEW' => ['CONTACTED', 'QUALIFIED', 'LOST', 'ARCHIVED'],
        'CONTACTED' => ['QUALIFIED', 'VIEWING_SCHEDULED', 'LOST', 'ARCHIVED'],
        'QUALIFIED' => ['VIEWING_SCHEDULED', 'NEGOTIATING', 'LOST', 'ARCHIVED'],
        'VIEWING_SCHEDULED' => ['NEGOTIATING', 'QUALIFIED', 'LOST', 'ARCHIVED'],
        'NEGOTIATING' => ['WON', 'LOST', 'ARCHIVED'],
        'WON' => ['ARCHIVED'],
        'LOST' => ['NEW', 'ARCHIVED'],
        'ARCHIVED' => ['NEW'],
    ];

    public function statistics(): JsonResponse
    {
        $this->authorize('leads:read');

        $totalLeads = Lead::count();

        $byStatus = Lead::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $byPriority = Lead::selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority');

        $bySource = Lead::selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->pluck('count', 'source');

        $recentLeads = Lead::where('created_at', '>=', now()->subDays(30))->count();
        $needsFollowUp = Lead::needsFollowUp()->count();
        $unassigned = Lead::whereNull('assigned_to')
            ->whereNotIn('status', [LeadStatus::WON->value, LeadStatus::LOST->value, LeadStatus::ARCHIVED->value])
            ->count();

        $avgResponseTime = Lead::whereNotNull('first_response_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (first_response_at - created_at))) as avg_seconds')
            ->value('avg_seconds');

        return $this->successResponse([
            'total_leads' => $totalLeads,
            'by_status' => $byStatus,
            'by_priority' => $byPriority,
            'by_source' => $bySource,
            'recent_leads' => $recentLeads,
            'needs_follow_up' => $needsFollowUp,
            'unassigned' => $unassigned,
            'avg_response_time_hours' => $avgResponseTime ? round($avgResponseTime / 3600, 1) : null,
        ], __('leads.statistics_retrieved'));
    }

    public function followUp(): JsonResponse
    {
        $this->authorize('leads:read');

        $leads = Lead::needsFollowUp()
            ->with(['property', 'agency', 'assignedTo', 'notes.createdBy'])
            ->orderBy('follow_up_date')
            ->get();

        return $this->successResponse(
            LeadResource::collection($leads),
            __('leads.follow_up_listed'),
        );
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('leads:read');

        $query = Lead::query()
            ->when($request->query('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('contact_first_name', 'ILIKE', "%{$s}%")
                    ->orWhere('contact_last_name', 'ILIKE', "%{$s}%")
                    ->orWhere('contact_email', 'ILIKE', "%{$s}%");
            }))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('priority'), fn ($q, $p) => $q->where('priority', $p))
            ->when($request->query('source'), fn ($q, $s) => $q->where('source', $s))
            ->when($request->query('inquiry_type'), fn ($q, $t) => $q->where('inquiry_type', $t))
            ->when($request->query('agency_id'), fn ($q, $id) => $q->where('agency_id', $id))
            ->when($request->query('property_id'), fn ($q, $id) => $q->where('property_id', $id))
            ->when($request->query('assigned_to'), fn ($q, $id) => $q->where('assigned_to', $id))
            ->when($request->has('unassigned'), fn ($q) => $q->whereNull('assigned_to'))
            ->with(['property', 'agency', 'assignedTo', 'notes.createdBy']);

        $sort = $request->query('sort', 'created_at');
        $order = $request->query('order', 'desc');
        $query->orderBy($sort, $order);

        $leads = $query->paginate(
            (int) $request->query('limit', '20'),
            ['*'],
            'page',
            (int) $request->query('page', '1'),
        );

        return $this->paginatedResponse(
            $leads->through(fn ($l) => new LeadResource($l)),
            __('leads.leads_listed'),
        );
    }

    public function byProperty(int $propertyId): JsonResponse
    {
        $this->authorize('leads:read');

        $leads = Lead::where('property_id', $propertyId)
            ->with(['property', 'agency', 'assignedTo', 'notes.createdBy'])
            ->orderByDesc('created_at')
            ->get();

        return $this->successResponse(
            LeadResource::collection($leads),
            __('leads.leads_listed'),
        );
    }

    public function show(int $id): JsonResponse
    {
        $this->authorize('leads:read');

        $lead = Lead::with(['property', 'agency', 'assignedTo', 'notes.createdBy'])->find($id);
        if (! $lead) {
            return $this->notFoundResponse(__('leads.not_found'));
        }

        return $this->successResponse(new LeadResource($lead), __('leads.lead_retrieved'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorize('leads:update');

        $lead = Lead::find($id);
        if (! $lead) {
            return $this->notFoundResponse(__('leads.not_found'));
        }

        $validated = $request->validate([
            'priority' => 'sometimes|string|in:' . implode(',', array_column(LeadPriority::cases(), 'value')),
            'viewing_scheduled_at' => 'nullable|date',
            'follow_up_date' => 'nullable|date',
            'close_reason' => 'nullable|string|max:500',
            'preferred_contact_method' => 'sometimes|string|in:email,phone',
        ]);

        $lead->update($validated);
        $lead->load(['property', 'agency', 'assignedTo', 'notes.createdBy']);

        return $this->successResponse(new LeadResource($lead), __('leads.lead_updated'));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $this->authorize('leads:update');

        $lead = Lead::find($id);
        if (! $lead) {
            return $this->notFoundResponse(__('leads.not_found'));
        }

        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', array_column(LeadStatus::cases(), 'value')),
            'close_reason' => 'nullable|string|max:500',
        ]);

        $currentStatus = $lead->status->value;
        $newStatus = $validated['status'];

        // Validate state transition
        $allowed = self::VALID_TRANSITIONS[$currentStatus] ?? [];
        if (! in_array($newStatus, $allowed)) {
            return $this->errorResponse(
                __('leads.invalid_status_transition', ['from' => $currentStatus, 'to' => $newStatus]),
                422,
            );
        }

        $updateData = ['status' => $newStatus];

        // Track first response
        if ($currentStatus === LeadStatus::NEW->value && $lead->first_response_at === null) {
            $updateData['first_response_at'] = now();
        }

        // Track closed
        if (in_array($newStatus, [LeadStatus::WON->value, LeadStatus::LOST->value])) {
            $updateData['closed_at'] = now();
            if (! empty($validated['close_reason'])) {
                $updateData['close_reason'] = $validated['close_reason'];
            }
        }

        $lead->update($updateData);
        $lead->load(['property', 'agency', 'assignedTo', 'notes.createdBy']);

        if (in_array($newStatus, [LeadStatus::WON->value, LeadStatus::LOST->value])) {
            LeadClosed::dispatch($lead, $newStatus);
        } else {
            LeadStatusChanged::dispatch($lead, $currentStatus, $newStatus);
        }

        return $this->successResponse(new LeadResource($lead), __('leads.status_updated'));
    }

    public function assign(Request $request, int $id): JsonResponse
    {
        $this->authorize('leads:manage');

        $lead = Lead::find($id);
        if (! $lead) {
            return $this->notFoundResponse(__('leads.not_found'));
        }

        $validated = $request->validate([
            'assigned_to' => 'required|integer|exists:users,id',
        ]);

        $lead->update(['assigned_to' => $validated['assigned_to']]);
        $lead->load(['property', 'agency', 'assignedTo', 'notes.createdBy']);

        LeadAssigned::dispatch($lead, $request->user()->id);

        return $this->successResponse(new LeadResource($lead), __('leads.lead_assigned'));
    }

    public function notes(int $id): JsonResponse
    {
        $this->authorize('leads:read');

        $lead = Lead::find($id);
        if (! $lead) {
            return $this->notFoundResponse(__('leads.not_found'));
        }

        return $this->successResponse(
            LeadNoteResource::collection($lead->notes()->with('createdBy')->get()),
            __('leads.notes_listed'),
        );
    }

    public function addNote(Request $request, int $id): JsonResponse
    {
        $this->authorize('leads:update');

        $lead = Lead::find($id);
        if (! $lead) {
            return $this->notFoundResponse(__('leads.not_found'));
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'is_internal' => 'sometimes|boolean',
        ]);

        $note = $lead->notes()->create([
            'content' => $validated['content'],
            'is_internal' => $validated['is_internal'] ?? true,
            'created_by' => $request->user()->id,
        ]);

        return $this->createdResponse(new LeadNoteResource($note), __('leads.note_added'));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorize('leads:delete');

        $lead = Lead::find($id);
        if (! $lead) {
            return $this->notFoundResponse(__('leads.not_found'));
        }

        $lead->delete();

        return $this->successResponse(null, __('leads.lead_deleted'));
    }
}
