<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Agency\Models\Agency;
use App\Domain\Lead\Enums\LeadPriority;
use App\Domain\Lead\Enums\LeadSource;
use App\Domain\Lead\Enums\LeadStatus;
use App\Domain\Lead\Events\LeadAssigned;
use App\Domain\Lead\Events\LeadClosed;
use App\Domain\Lead\Events\LeadStatusChanged;
use App\Domain\Lead\Models\Lead;
use App\Domain\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class LeadController extends Controller
{
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

    public function index(Request $request): Response
    {
        $filters = $request->only([
            'page', 'limit', 'sort', 'order', 'search',
            'status', 'priority', 'source', 'agency_id', 'assigned_to',
        ]);

        $query = Lead::query()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('contact_first_name', 'ILIKE', "%{$s}%")
                    ->orWhere('contact_last_name', 'ILIKE', "%{$s}%")
                    ->orWhere('contact_email', 'ILIKE', "%{$s}%");
            }))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['priority'] ?? null, fn ($q, $p) => $q->where('priority', $p))
            ->when($filters['source'] ?? null, fn ($q, $s) => $q->where('source', $s))
            ->when($filters['agency_id'] ?? null, fn ($q, $id) => $q->where('agency_id', $id))
            ->when($filters['assigned_to'] ?? null, fn ($q, $id) => $q->where('assigned_to', $id))
            ->with(['property', 'agency', 'assignedTo']);

        $sort = $filters['sort'] ?? 'created_at';
        $order = $filters['order'] ?? 'desc';
        $query->orderBy($sort, $order);

        $leads = $query->paginate(
            (int) ($filters['limit'] ?? 20),
            ['*'],
            'page',
            (int) ($filters['page'] ?? 1),
        );

        // Statistics for header summary
        $stats = [
            'total' => Lead::count(),
            'new' => Lead::where('status', LeadStatus::NEW)->count(),
            'needs_follow_up' => Lead::needsFollowUp()->count(),
            'unassigned' => Lead::whereNull('assigned_to')
                ->whereNotIn('status', [LeadStatus::WON->value, LeadStatus::LOST->value, LeadStatus::ARCHIVED->value])
                ->count(),
        ];

        return Inertia::render('Leads/Index', [
            'leads' => $this->paginateToArray($leads),
            'filters' => $filters,
            'stats' => $stats,
            'agencies' => fn () => Agency::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'agents' => fn () => User::whereIn('user_type', ['agent', 'agency_admin', 'platform_admin', 'super_admin'])
                ->where('status', 'active')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function show(Lead $lead): Response
    {
        $lead->load(['property.category', 'property.canton', 'property.city', 'agency', 'user', 'assignedTo', 'notes.createdBy']);

        // Get available transitions for current status
        $currentStatus = $lead->status->value;
        $availableTransitions = self::VALID_TRANSITIONS[$currentStatus] ?? [];

        return Inertia::render('Leads/Show', [
            'lead' => $this->leadToArray($lead),
            'availableTransitions' => $availableTransitions,
            'agents' => fn () => User::whereIn('user_type', ['agent', 'agency_admin', 'platform_admin', 'super_admin'])
                ->where('status', 'active')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'priority' => 'sometimes|string|in:' . implode(',', array_column(LeadPriority::cases(), 'value')),
            'viewing_scheduled_at' => 'nullable|date',
            'follow_up_date' => 'nullable|date',
            'close_reason' => 'nullable|string|max:500',
            'preferred_contact_method' => 'sometimes|string|in:email,phone',
        ]);

        $lead->update($validated);

        return redirect()->back()->with('success', __('leads.lead_updated'));
    }

    public function updateStatus(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', array_column(LeadStatus::cases(), 'value')),
            'close_reason' => 'nullable|string|max:500',
        ]);

        $currentStatus = $lead->status->value;
        $newStatus = $validated['status'];

        $allowed = self::VALID_TRANSITIONS[$currentStatus] ?? [];
        if (! in_array($newStatus, $allowed)) {
            return redirect()->back()->with('error', __('leads.invalid_status_transition', [
                'from' => $currentStatus,
                'to' => $newStatus,
            ]));
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
        }

        $lead->update($updateData);

        if (in_array($newStatus, [LeadStatus::WON->value, LeadStatus::LOST->value])) {
            LeadClosed::dispatch($lead, $newStatus);
        } else {
            LeadStatusChanged::dispatch($lead, $currentStatus, $newStatus);
        }

        return redirect()->back()->with('success', __('leads.status_updated'));
    }

    public function assign(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_to' => 'required|integer|exists:users,id',
        ]);

        $lead->update(['assigned_to' => $validated['assigned_to']]);

        LeadAssigned::dispatch($lead, $request->user()->id);

        return redirect()->back()->with('success', __('leads.lead_assigned'));
    }

    public function addNote(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'is_internal' => 'sometimes|boolean',
        ]);

        $lead->notes()->create([
            'content' => $validated['content'],
            'is_internal' => $validated['is_internal'] ?? true,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->back()->with('success', __('leads.note_added'));
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()
            ->route('admin.leads.index')
            ->with('success', __('leads.lead_deleted'));
    }

    // ── Helpers ──

    private function paginateToArray($paginator): array
    {
        return [
            'data' => collect($paginator->items())->map(fn ($l) => $this->leadToArray($l)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];
    }

    private function leadToArray(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'property_id' => $lead->property_id,
            'agency_id' => $lead->agency_id,
            'user_id' => $lead->user_id,
            'assigned_to' => $lead->assigned_to,
            'contact_first_name' => $lead->contact_first_name,
            'contact_last_name' => $lead->contact_last_name,
            'contact_email' => $lead->contact_email,
            'contact_phone' => $lead->contact_phone,
            'preferred_contact_method' => $lead->preferred_contact_method?->value,
            'preferred_language' => $lead->preferred_language,
            'inquiry_type' => $lead->inquiry_type?->value,
            'message' => $lead->message,
            'status' => $lead->status->value,
            'priority' => $lead->priority->value,
            'source' => $lead->source->value,
            'viewing_scheduled_at' => $lead->viewing_scheduled_at?->toISOString(),
            'follow_up_date' => $lead->follow_up_date?->toDateString(),
            'first_response_at' => $lead->first_response_at?->toISOString(),
            'closed_at' => $lead->closed_at?->toISOString(),
            'close_reason' => $lead->close_reason,
            'created_at' => $lead->created_at?->toISOString(),
            'updated_at' => $lead->updated_at?->toISOString(),
            'property' => $lead->relationLoaded('property') && $lead->property ? [
                'id' => $lead->property->id,
                'external_id' => $lead->property->external_id,
                'address' => $lead->property->address,
                'price' => $lead->property->price,
                'currency' => $lead->property->currency,
                'status' => $lead->property->status->value,
                'transaction_type' => $lead->property->transaction_type->value,
                'category' => $lead->property->relationLoaded('category') && $lead->property->category ? [
                    'id' => $lead->property->category->id,
                    'name' => $lead->property->category->getTranslation('name', app()->getLocale()),
                ] : null,
                'canton' => $lead->property->relationLoaded('canton') && $lead->property->canton ? [
                    'id' => $lead->property->canton->id,
                    'code' => $lead->property->canton->code,
                ] : null,
                'city' => $lead->property->relationLoaded('city') && $lead->property->city ? [
                    'id' => $lead->property->city->id,
                    'name' => $lead->property->city->getTranslation('name', app()->getLocale()),
                ] : null,
            ] : null,
            'agency' => $lead->relationLoaded('agency') && $lead->agency ? [
                'id' => $lead->agency->id,
                'name' => $lead->agency->name,
            ] : null,
            'user' => $lead->relationLoaded('user') && $lead->user ? [
                'id' => $lead->user->id,
                'first_name' => $lead->user->first_name,
                'last_name' => $lead->user->last_name,
                'email' => $lead->user->email,
            ] : null,
            'assigned_user' => $lead->relationLoaded('assignedTo') && $lead->assignedTo ? [
                'id' => $lead->assignedTo->id,
                'first_name' => $lead->assignedTo->first_name,
                'last_name' => $lead->assignedTo->last_name,
            ] : null,
            'notes' => $lead->relationLoaded('notes')
                ? $lead->notes->map(fn ($n) => [
                    'id' => $n->id,
                    'lead_id' => $n->lead_id,
                    'content' => $n->content,
                    'is_internal' => $n->is_internal,
                    'created_by' => $n->created_by,
                    'created_at' => $n->created_at?->toISOString(),
                    'creator' => $n->relationLoaded('createdBy') && $n->createdBy ? [
                        'id' => $n->createdBy->id,
                        'first_name' => $n->createdBy->first_name,
                        'last_name' => $n->createdBy->last_name,
                    ] : null,
                ])->toArray()
                : [],
        ];
    }
}
