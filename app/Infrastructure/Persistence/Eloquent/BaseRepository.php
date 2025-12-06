<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    public function __construct(string $modelClass)
    {
        $this->model = new $modelClass;
    }

    public function findById(int $id): ?Model
    {
        return $this->model::find($id);
    }

    public function findByIdOrFail(int $id): Model
    {
        return $this->model::findOrFail($id);
    }

    public function all(array $filters = []): Collection
    {
        return $this->model::query()->get();
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->model::query()->paginate($perPage);
    }

    public function create(array $data): Model
    {
        return $this->model::create($data);
    }

    public function update(int $id, array $data): Model
    {
        $model = $this->findByIdOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(int $id): bool
    {
        $model = $this->findByIdOrFail($id);

        return (bool) $model->delete();
    }

    protected function newQuery()
    {
        return $this->model::query();
    }
}
