<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Agency;

use App\Domain\Lead\Enums\LeadPriority;
use App\Domain\Lead\Enums\LeadStatus;
use App\Domain\Lead\Events\LeadAssigned;
use App\Domain\Lead\Events\LeadClosed;
use App\Domain\Lead\Events\LeadStatusChanged;
use App\Domain\Lead\Models\Lead;
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

    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('leads:read');

        $agencyId = $this->resolveAgencyId($request);

        $query = Lead::where('agency_id', $agencyId);

        $totalLeads = $query->clone()->count();

        $byStatus = $query->clone()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $byPriority = $query->clone()
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority');

        $recentLeads = $query->clone()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $needsFollowUp = $query->clone()
            ->needsFollowUp()
            ->count();

        $unassigned = $query->clone()
            ->whereNull('assigned_to')
            ->whereNotIn('status', [LeadStatus::WON->value, LeadStatus::LOST->value, LeadStatus::ARCHIVED->value])
            ->count();

        return $this->successResponse([
            'total_leads' => $totalLeads,
            'by_status' => $byStatus,
            'by_priority' => $byPriority,
            'recent_leads' => $recentLeads,
            'needs_follow_up' => $needsFollowUp,
            'unassigned' => $unassigned,
        ], __('leads.statistics_retrieved'));
    }

    public function followUp(Request $request): JsonResponse
    {
        $this->authorize('leads:read');

        $agencyId = $this->resolveAgencyId($request);

        $leads = Lead::where('agency_id', $agencyId)
            ->needsFollowUp()
            ->with(['property', 'assignedTo', 'notes.createdBy'])
            ->orderBy('follow_up_date')
            ->get();

        return $this->successResponse(
            LeadResource::collection($leads),
            __('leads.follow_up_retrieved'),
        );
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('leads:read');

        $agencyId = $this->resolveAgencyId($request);

        $query = Lead::where('agency_id', $agencyId)
            ->when($request->query('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('contact_first_name', 'ILIKE', "%{$s}%")
                    ->orWhere('contact_last_name', 'ILIKE', "%{$s}%")
                    ->orWhere('contact_email', 'ILIKE', "%{$s}%");
            }))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('priority'), fn ($q, $p) => $q->where('priority', $p))
            ->when($request->query('property_id'), fn ($q, $id) => $q->where('property_id', $id))
            ->when($request->query('assigned_to'), fn ($q, $id) => $q->where('assigned_to', $id))
            ->when($request->has('unassigned'), fn ($q) => $q->whereNull('assigned_to'))
            ->with(['property', 'assignedTo', 'notes.createdBy']);

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
            __('leads.list_retrieved'),
        );
    }

    public function byProperty(Request $request, int $propertyId): JsonResponse
    {
        $this->authorize('leads:read');

        $agencyId = $this->resolveAgencyId($request);

        $leads = Lead::where('agency_id', $agencyId)
            ->where('property_id', $propertyId)
            ->with(['property', 'assignedTo', 'notes.createdBy'])
            ->orderByDesc('created_at')
            ->get();

        return $this->successResponse(
            LeadResource::collection($leads),
            __('leads.list_retrieved'),
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorize('leads:read');

        $agencyId = $this->resolveAgencyId($request);

        $lead = Lead::with(['property', 'assignedTo', 'notes.createdBy'])
            ->where('agency_id', $agencyId)
            ->find($id);

        if (! $lead) {
            return $this->notFoundResponse(__('leads.not_found'));
        }

        return $this->successResponse(new LeadResource($lead), __('leads.retrieved'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorize('leads:update');

        $agencyId = $this->resolveAgencyId($request);

        $lead = Lead::where('agency_id', $agencyId)->find($id);
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
        $lead->load(['property', 'assignedTo', 'notes.createdBy']);

        return $this->successResponse(new LeadResource($lead), __('leads.updated'));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $this->authorize('leads:update');

        $agencyId = $this->resolveAgencyId($request);

        $lead = Lead::where('agency_id', $agencyId)->find($id);
        if (! $lead) {
            return $this->notFoundResponse(__('leads.not_found'));
        }

        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', array_column(LeadStatus::cases(), 'value')),
            'close_reason' => 'nullable|string|max:500',
        ]);

        $currentStatus = $lead->status->value;
        $newStatus = $validated['status'];

        $allowed = self::VALID_TRANSITIONS[$currentStatus] ?? [];
        if (! in_array($newStatus, $allowed)) {
            return $this->errorResponse(
                __('leads.invalid_status_transition', ['from' => $currentStatus, 'to' => $newStatus]),
                422,
            );
        }

        $updateData = ['status' => $newStatus];

        if ($currentStatus === LeadStatus::NEW->value && $lead->first_response_at === null) {
            $updateData['first_response_at'] = now();
        }

        if (in_array($newStatus, [LeadStatus::WON->value, LeadStatus::LOST->value])) {
            $updateData['closed_at'] = now();
            if (! empty($validated['close_reason'])) {
                $updateData['close_reason'] = $validated['close_reason'];
            }
            $lead->update($updateData);
            $lead->load(['property', 'assignedTo', 'notes.createdBy']);

            LeadClosed::dispatch($lead, $newStatus);
        } else {
            $lead->update($updateData);
            $lead->load(['property', 'assignedTo', 'notes.createdBy']);

            LeadStatusChanged::dispatch($lead, $currentStatus, $newStatus);
        }

        return $this->successResponse(new LeadResource($lead), __('leads.status_updated'));
    }

    public function assign(Request $request, int $id): JsonResponse
    {
        $this->authorize('leads:manage');

        $agencyId = $this->resolveAgencyId($request);

        $lead = Lead::where('agency_id', $agencyId)->find($id);
        if (! $lead) {
            return $this->notFoundResponse(__('leads.not_found'));
        }

        $validated = $request->validate([
            'assigned_to' => 'required|integer|exists:users,id',
        ]);

        $lead->update(['assigned_to' => $validated['assigned_to']]);
        $lead->load(['property', 'assignedTo', 'notes.createdBy']);

        LeadAssigned::dispatch($lead, $request->user()->id);

        return $this->successResponse(new LeadResource($lead), __('leads.assigned'));
    }

    public function notes(Request $request, int $id): JsonResponse
    {
        $this->authorize('leads:read');

        $agencyId = $this->resolveAgencyId($request);

        $lead = Lead::where('agency_id', $agencyId)->find($id);
        if (! $lead) {
            return $this->notFoundResponse(__('leads.not_found'));
        }

        return $this->successResponse(
            LeadNoteResource::collection($lead->notes()->with('createdBy')->get()),
            __('leads.notes_retrieved'),
        );
    }

    public function addNote(Request $request, int $id): JsonResponse
    {
        $this->authorize('leads:update');

        $agencyId = $this->resolveAgencyId($request);

        $lead = Lead::where('agency_id', $agencyId)->find($id);
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

    private function resolveAgencyId(Request $request): int
    {
        $user = $request->user();

        // Admins can pass agency_id as query param
        $userType = $user->user_type instanceof \App\Domain\User\Enums\UserType
            ? $user->user_type
            : \App\Domain\User\Enums\UserType::tryFrom($user->user_type);

        if ($userType?->isAdmin() && $request->has('agency_id')) {
            return (int) $request->query('agency_id');
        }

        return (int) $user->agency_id;
    }
}
