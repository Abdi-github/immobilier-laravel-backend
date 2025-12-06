<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Property\Models\PropertyImage;
use App\Domain\Property\Repositories\PropertyImageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class EloquentPropertyImageRepository extends BaseRepository implements PropertyImageRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(PropertyImage::class);
    }

    public function findByProperty(int $propertyId): Collection
    {
        return PropertyImage::where('property_id', $propertyId)
            ->orderBy('sort_order')
            ->get();
    }

    public function reorder(int $propertyId, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            PropertyImage::where('id', $id)
                ->where('property_id', $propertyId)
                ->update(['sort_order' => $index]);
        }
    }

    public function setPrimary(int $propertyId, int $imageId): void
    {
        PropertyImage::where('property_id', $propertyId)->update(['is_primary' => false]);
        PropertyImage::where('id', $imageId)
            ->where('property_id', $propertyId)
            ->update(['is_primary' => true]);
    }

    public function countByProperty(int $propertyId): int
    {
        return PropertyImage::where('property_id', $propertyId)->count();
    }

    public function deleteByProperty(int $propertyId): int
    {
        return PropertyImage::where('property_id', $propertyId)->delete();
    }

    public function findByPropertyAndId(int $propertyId, int $imageId): ?PropertyImage
    {
        return PropertyImage::where('property_id', $propertyId)
            ->where('id', $imageId)
            ->first();
    }
}
