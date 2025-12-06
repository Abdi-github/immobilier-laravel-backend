<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Agency\Models\Agency;
use App\Domain\Agency\Repositories\AgencyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentAgencyRepository extends BaseRepository implements AgencyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Agency::class);
    }

    public function findBySlug(string $slug): ?Agency
    {
        return Agency::where('slug', $slug)->first();
    }

    public function findByCanton(int $cantonId, array $filters = []): LengthAwarePaginator
    {
        return Agency::query()
            ->where('canton_id', $cantonId)
            ->active()
            ->with(['canton', 'city'])
            ->orderBy('name')
            ->paginate($filters['limit'] ?? 20);
    }

    public function findByCity(int $cityId, array $filters = []): LengthAwarePaginator
    {
        return Agency::query()
            ->where('city_id', $cityId)
            ->active()
            ->with(['canton', 'city'])
            ->orderBy('name')
            ->paginate($filters['limit'] ?? 20);
    }

    public function getStatistics(): array
    {
        return [
            'total' => Agency::count(),
            'active' => Agency::where('status', 'active')->count(),
            'verified' => Agency::where('is_verified', true)->count(),
            'unverified' => Agency::where('is_verified', false)->count(),
        ];
    }
}
