<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Agent;

use App\Domain\Lead\Models\Lead;
use App\Http\Controllers\Controller;
use App\Http\Resources\LeadResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LeadController extends Controller
{
    use ApiResponse;

    public function followUp(Request $request): JsonResponse
    {
        $this->authorize('leads:read');

        $paginator = Lead::query()
            ->where('assigned_to', $request->user()->id)
            ->whereNotNull('follow_up_date')
            ->where('follow_up_date', '<=', now()->toDateString())
            ->whereNotIn('status', ['WON', 'LOST', 'ARCHIVED'])
            ->with(['property.primaryImage', 'property.translations', 'agency'])
            ->orderBy('follow_up_date')
            ->paginate($request->integer('limit', 20), ['*'], 'page', $request->integer('page', 1));

        $paginator->through(fn ($l) => new LeadResource($l));

        return $this->paginatedResponse($paginator, __('leads.follow_up_retrieved'));
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('leads:read');

        $paginator = Lead::query()
            ->where('assigned_to', $request->user()->id)
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('priority'), fn ($q, $p) => $q->where('priority', $p))
            ->when($request->query('search'), function ($q, $search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('contact_first_name', 'ILIKE', "%{$search}%")
                        ->orWhere('contact_last_name', 'ILIKE', "%{$search}%")
                        ->orWhere('contact_email', 'ILIKE', "%{$search}%");
                });
            })
            ->with(['property.primaryImage', 'property.translations', 'agency'])
            ->orderByDesc('created_at')
            ->paginate($request->integer('limit', 20), ['*'], 'page', $request->integer('page', 1));

        $paginator->through(fn ($l) => new LeadResource($l));

        return $this->paginatedResponse($paginator, __('leads.list_retrieved'));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorize('leads:read');

        $lead = Lead::with(['property.primaryImage', 'property.translations', 'agency', 'notes.createdBy', 'assignedTo'])
            ->where('assigned_to', $request->user()->id)
            ->find($id);

        if (! $lead) {
            return $this->notFoundResponse(__('leads.not_found'));
        }

        return $this->successResponse(new LeadResource($lead), __('leads.retrieved'));
    }
}
