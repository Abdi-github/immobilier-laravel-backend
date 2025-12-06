<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Location\Models\Canton;
use App\Domain\Location\Repositories\CantonRepositoryInterface;

final class EloquentCantonRepository extends BaseRepository implements CantonRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Canton::class);
    }

    public function findByCode(string $code): ?Canton
    {
        return Canton::where('code', $code)->first();
    }
}
