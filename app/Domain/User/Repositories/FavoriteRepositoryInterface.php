<?php

declare(strict_types=1);

namespace App\Domain\User\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface FavoriteRepositoryInterface extends RepositoryInterface
{
    public function findByUser(int $userId): Collection;

    public function findByUserPaginated(int $userId, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    public function getFavoritePropertyIds(int $userId): array;

    public function isFavorited(int $userId, int $propertyId): bool;

    public function toggle(int $userId, int $propertyId): bool;
}
