<?php

declare(strict_types=1);

namespace App\Domain\Location\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CityRepositoryInterface extends RepositoryInterface
{
    public function findByCanton(int $cantonId): Collection;

    public function findByPostalCode(string $postalCode): Collection;

    public function searchByName(string $query, int $limit = 20): Collection;

    public function getPopular(int $limit = 10): Collection;

    public function findByCantonPaginated(int $cantonId, array $filters = []): LengthAwarePaginator;

    public function findAllFiltered(array $filters = []): LengthAwarePaginator;
}
