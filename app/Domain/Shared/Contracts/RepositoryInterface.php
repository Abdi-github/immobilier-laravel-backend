<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface RepositoryInterface
{
    public function findById(int $id): ?Model;

    public function findByIdOrFail(int $id): Model;

    public function all(array $filters = []): Collection;

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function create(array $data): Model;

    public function update(int $id, array $data): Model;

    public function delete(int $id): bool;
}
