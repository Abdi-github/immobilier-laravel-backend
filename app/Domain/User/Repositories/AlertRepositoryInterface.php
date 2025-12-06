<?php

declare(strict_types=1);

namespace App\Domain\User\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface AlertRepositoryInterface extends RepositoryInterface
{
    public function findByUser(int $userId): Collection;

    public function findActiveByFrequency(string $frequency): Collection;

    public function toggle(int $id): bool;
}
