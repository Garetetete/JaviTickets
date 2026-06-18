<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Eloquent: CRUD + soft-delete/restore reutilizable.
 * Cada repositorio concreto define $model y añade sus métodos específicos.
 * Todo acceso a Eloquent vive aquí (regla de capas: nada de Model::query()
 * fuera de App\Repositories\Eloquent).
 */
abstract class EloquentRepository implements RepositoryInterface
{
    /** @var class-string<Model> */
    protected string $model;

    public function find(int $id): ?Model
    {
        return $this->model::query()->find($id);
    }

    public function paginate(array $filters = [], int $perPage = 20, bool $withTrashed = false): LengthAwarePaginator
    {
        $query = $this->model::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        foreach ($filters as $column => $value) {
            if ($value !== null) {
                $query->where($column, $value);
            }
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(array $data): Model
    {
        return $this->model::query()->create($data);
    }

    public function update(int $id, array $data): Model
    {
        $model = $this->model::query()->findOrFail($id);
        $model->update($data);

        return $model->refresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model::query()->findOrFail($id)->delete();
    }

    public function restore(int $id): bool
    {
        return (bool) $this->model::withTrashed()->findOrFail($id)->restore();
    }
}
