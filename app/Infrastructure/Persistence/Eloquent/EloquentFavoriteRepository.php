<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\User\Models\Favorite;
use App\Domain\User\Repositories\FavoriteRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class EloquentFavoriteRepository extends BaseRepository implements FavoriteRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Favorite::class);
    }

    public function findByUser(int $userId): Collection
    {
        return Favorite::where('user_id', $userId)
            ->with('property.primaryImage', 'property.category', 'property.canton', 'property.city')
            ->orderByDesc('created_at')
            ->get();
    }

    public function findByUserPaginated(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return Favorite::where('user_id', $userId)
            ->with('property.primaryImage', 'property.category', 'property.canton', 'property.city')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getFavoritePropertyIds(int $userId): array
    {
        return Favorite::where('user_id', $userId)
            ->pluck('property_id')
            ->toArray();
    }

    public function isFavorited(int $userId, int $propertyId): bool
    {
        return Favorite::where('user_id', $userId)
            ->where('property_id', $propertyId)
            ->exists();
    }

    public function toggle(int $userId, int $propertyId): bool
    {
        $existing = Favorite::where('user_id', $userId)
            ->where('property_id', $propertyId)
            ->first();

        if ($existing) {
            $existing->delete();

            return false; // removed
        }

        Favorite::create([
            'user_id' => $userId,
            'property_id' => $propertyId,
        ]);

        return true; // added
    }
}
