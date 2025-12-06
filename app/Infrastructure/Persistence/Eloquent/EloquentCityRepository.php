<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Location\Models\City;
use App\Domain\Location\Repositories\CityRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class EloquentCityRepository extends BaseRepository implements CityRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(City::class);
    }

    public function findByCanton(int $cantonId): Collection
    {
        return City::where('canton_id', $cantonId)
            ->active()
            ->orderBy('postal_code')
            ->get();
    }

    public function findByPostalCode(string $postalCode): Collection
    {
        return City::where('postal_code', $postalCode)->active()->get();
    }

    public function searchByName(string $query, int $limit = 20): Collection
    {
        return City::query()
            ->active()
            ->whereRaw("name::text ILIKE ?", ["%{$query}%"])
            ->with('canton')
            ->limit($limit)
            ->get();
    }

    public function getPopular(int $limit = 10): Collection
    {
        return City::query()
            ->active()
            ->whereNotNull('image_url')
            ->with('canton')
            ->limit($limit)
            ->get();
    }

    public function findByCantonPaginated(int $cantonId, array $filters = []): LengthAwarePaginator
    {
        return City::query()
            ->where('canton_id', $cantonId)
            ->when(
                isset($filters['is_active']),
                fn ($q) => $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN)),
                fn ($q) => $q->active(),
            )
            ->when($filters['postal_code'] ?? null, fn ($q, $pc) => $q->where('postal_code', $pc))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->whereRaw("name::text ILIKE ?", ["%{$s}%"]))
            ->with('canton')
            ->orderBy($filters['sort'] ?? 'postal_code', $filters['order'] ?? 'asc')
            ->paginate($filters['limit'] ?? 20, ['*'], 'page', $filters['page'] ?? 1);
    }

    public function findAllFiltered(array $filters = []): LengthAwarePaginator
    {
        return City::query()
            ->when(
                isset($filters['is_active']),
                fn ($q) => $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN)),
            )
            ->when($filters['canton_id'] ?? null, fn ($q, $id) => $q->where('canton_id', $id))
            ->when($filters['postal_code'] ?? null, fn ($q, $pc) => $q->where('postal_code', $pc))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->whereRaw("name::text ILIKE ?", ["%{$s}%"]))
            ->with('canton')
            ->orderBy($filters['sort'] ?? 'postal_code', $filters['order'] ?? 'asc')
            ->paginate($filters['limit'] ?? 20, ['*'], 'page', $filters['page'] ?? 1);
    }
}
