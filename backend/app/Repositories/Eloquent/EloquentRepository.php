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
    /**
     * Clase del modelo Eloquent gestionado por el repositorio concreto.
     *
     * @var class-string<Model>
     */
    protected string $model;

    /**
     * {@inheritDoc}
     *
     * Delega en find() del modelo configurado en $model.
     */
    public function find(int $id): ?Model
    {
        return $this->model::query()->find($id);
    }

    /**
     * {@inheritDoc}
     *
     * Aplica cada filtro como where de igualdad (ignora valores null) y
     * opcionalmente incluye los registros soft-deleted con withTrashed().
     */
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

    /**
     * {@inheritDoc}
     *
     * Crea el registro mediante asignación masiva sobre el modelo configurado.
     */
    public function create(array $data): Model
    {
        return $this->model::query()->create($data);
    }

    /**
     * {@inheritDoc}
     *
     * Localiza el registro (findOrFail), aplica el update y devuelve el modelo
     * recargado desde la BD.
     */
    public function update(int $id, array $data): Model
    {
        $model = $this->model::query()->findOrFail($id);
        $model->update($data);

        return $model->refresh();
    }

    /**
     * {@inheritDoc}
     *
     * Realiza un soft-delete sobre el registro localizado con findOrFail.
     */
    public function delete(int $id): bool
    {
        return (bool) $this->model::query()->findOrFail($id)->delete();
    }

    /**
     * {@inheritDoc}
     *
     * Recupera el registro incluyendo los soft-deleted (withTrashed) y lo restaura.
     */
    public function restore(int $id): bool
    {
        return (bool) $this->model::withTrashed()->findOrFail($id)->restore();
    }
}
