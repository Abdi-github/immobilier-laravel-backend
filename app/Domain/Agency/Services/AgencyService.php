<?php

declare(strict_types=1);

namespace App\Domain\Agency\Services;

use App\Domain\Agency\Models\Agency;
use App\Domain\Agency\Repositories\AgencyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class AgencyService
{
    public function __construct(
        private readonly AgencyRepositoryInterface $agencyRepository,
    ) {}

    public function getAllAgencies(array $filters = []): LengthAwarePaginator
    {
        $query = Agency::query()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'ILIKE', "%{$s}%")
                    ->orWhere('address', 'ILIKE', "%{$s}%");
            }))
            ->when($filters['canton_id'] ?? null, fn ($q, $id) => $q->where('canton_id', $id))
            ->when($filters['city_id'] ?? null, fn ($q, $id) => $q->where('city_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when(isset($filters['is_verified']), fn ($q) => $q->where('is_verified', filter_var($filters['is_verified'], FILTER_VALIDATE_BOOLEAN)))
            ->when(
                ! ($filters['include_inactive'] ?? false),
                fn ($q) => $q->active(),
            )
            ->with(['canton', 'city']);

        $sortField = $filters['sort'] ?? 'name';
        $sortDirection = $filters['order'] ?? 'asc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($filters['limit'] ?? 20, ['*'], 'page', $filters['page'] ?? 1);
    }

    public function getAgencyById(int $id): ?Agency
    {
        return Agency::with(['canton', 'city'])->find($id);
    }

    public function getAgencyBySlug(string $slug): ?Agency
    {
        $agency = $this->agencyRepository->findBySlug($slug);
        $agency?->load(['canton', 'city']);

        return $agency;
    }

    public function getAgenciesByCanton(int $cantonId, array $filters = []): LengthAwarePaginator
    {
        return $this->agencyRepository->findByCanton($cantonId, $filters);
    }

    public function getAgenciesByCity(int $cityId, array $filters = []): LengthAwarePaginator
    {
        return $this->agencyRepository->findByCity($cityId, $filters);
    }
}
