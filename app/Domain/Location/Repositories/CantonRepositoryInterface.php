<?php

declare(strict_types=1);

namespace App\Domain\Location\Repositories;

use App\Domain\Location\Models\Canton;
use App\Domain\Shared\Contracts\RepositoryInterface;

interface CantonRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code): ?Canton;
}
