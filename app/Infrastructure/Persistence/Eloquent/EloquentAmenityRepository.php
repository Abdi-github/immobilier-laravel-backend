<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Catalog\Models\Amenity;
use App\Domain\Catalog\Repositories\AmenityRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class EloquentAmenityRepository extends BaseRepository implements AmenityRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Amenity::class);
    }

    public function findByGroup(string $group): Collection
    {
        return Amenity::where('group', $group)
            ->active()
            ->ordered()
            ->get();
    }
}
