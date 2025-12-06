<?php

declare(strict_types=1);

namespace App\Domain\Agency\Repositories;

use App\Domain\Agency\Models\Agency;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AgencyRepositoryInterface extends RepositoryInterface
{
    public function findBySlug(string $slug): ?Agency;

    public function findByCanton(int $cantonId, array $filters = []): LengthAwarePaginator;

    public function findByCity(int $cityId, array $filters = []): LengthAwarePaginator;

    public function getStatistics(): array;
}
