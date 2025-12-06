<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Lead\Models\Lead;
use App\Domain\Lead\Repositories\LeadRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentLeadRepository extends BaseRepository implements LeadRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Lead::class);
    }

    public function findByAgency(int $agencyId, array $filters = []): LengthAwarePaginator
    {
        return Lead::query()
            ->where('agency_id', $agencyId)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['priority'] ?? null, fn ($q, $p) => $q->where('priority', $p))
            ->with(['property', 'assignedTo'])
            ->orderByDesc('created_at')
            ->paginate($filters['limit'] ?? 20);
    }

    public function findByProperty(int $propertyId, array $filters = []): LengthAwarePaginator
    {
        return Lead::query()
            ->where('property_id', $propertyId)
            ->with(['user', 'assignedTo'])
            ->orderByDesc('created_at')
            ->paginate($filters['limit'] ?? 20);
    }

    public function findAssignedTo(int $userId, array $filters = []): LengthAwarePaginator
    {
        return Lead::query()
            ->where('assigned_to', $userId)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->with(['property', 'agency'])
            ->orderByDesc('created_at')
            ->paginate($filters['limit'] ?? 20);
    }

    public function findNeedingFollowUp(array $filters = []): LengthAwarePaginator
    {
        return Lead::query()
            ->needsFollowUp()
            ->when($filters['agency_id'] ?? null, fn ($q, $id) => $q->where('agency_id', $id))
            ->with(['property', 'assignedTo'])
            ->orderBy('follow_up_date')
            ->paginate($filters['limit'] ?? 20);
    }

    public function getStatistics(?int $agencyId = null): array
    {
        $query = Lead::query()
            ->when($agencyId, fn ($q, $id) => $q->where('agency_id', $id));

        return [
            'total' => $query->clone()->count(),
            'new' => $query->clone()->where('status', 'NEW')->count(),
            'contacted' => $query->clone()->where('status', 'CONTACTED')->count(),
            'qualified' => $query->clone()->where('status', 'QUALIFIED')->count(),
            'won' => $query->clone()->where('status', 'WON')->count(),
            'lost' => $query->clone()->where('status', 'LOST')->count(),
        ];
    }
}
