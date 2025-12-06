<?php

declare(strict_types=1);

namespace App\Domain\Location\Services;

use App\Domain\Location\Models\Canton;
use App\Domain\Location\Repositories\CantonRepositoryInterface;
use App\Domain\Location\Repositories\CityRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class LocationService
{
    public function __construct(
        private readonly CantonRepositoryInterface $cantonRepository,
        private readonly CityRepositoryInterface $cityRepository,
    ) {}

    // ── Cantons ──

    public function getAllCantons(array $filters = []): LengthAwarePaginator
    {
        $query = Canton::query()
            ->when(
                isset($filters['is_active']),
                fn ($q) => $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN)),
            )
            ->when(
                $filters['code'] ?? null,
                fn ($q, $code) => $q->where('code', strtoupper($code)),
            )
            ->when(
                $filters['search'] ?? null,
                fn ($q, $search) => $q->whereRaw("name::text ILIKE ?", ["%{$search}%"]),
            );

        $sortField = $filters['sort'] ?? 'code';
        $sortDirection = $filters['order'] ?? 'asc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($filters['limit'] ?? 20, ['*'], 'page', $filters['page'] ?? 1);
    }

    public function getCantonById(int $id): ?Canton
    {
        return $this->cantonRepository->findById($id);
    }

    public function getCantonByCode(string $code): ?Canton
    {
        return $this->cantonRepository->findByCode(strtoupper($code));
    }

    public function getCantonCities(int $cantonId, array $filters = []): LengthAwarePaginator
    {
        return $this->cityRepository->findByCantonPaginated($cantonId, $filters);
    }

    // ── Cities ──

    public function getAllCities(array $filters = []): LengthAwarePaginator
    {
        return $this->cityRepository->findAllFiltered($filters);
    }

    public function getCityById(int $id): ?\App\Domain\Location\Models\City
    {
        $city = $this->cityRepository->findById($id);
        $city?->load('canton');

        return $city;
    }

    public function getPopularCities(int $limit = 12, int $minProperties = 10): Collection
    {
        return $this->cityRepository->getPopular($limit);
    }

    public function searchCities(string $query, int $limit = 20): Collection
    {
        return $this->cityRepository->searchByName($query, $limit);
    }

    public function getCitiesByPostalCode(string $postalCode): Collection
    {
        return $this->cityRepository->findByPostalCode($postalCode);
    }
}
