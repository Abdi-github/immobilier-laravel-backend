<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface AmenityRepositoryInterface extends RepositoryInterface
{
    public function findByGroup(string $group): Collection;
}
