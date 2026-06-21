<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * Contrato base de CRUD + soft-delete/restore para agregados de negocio.
 * Las interfaces específicas extienden esta y añaden sus métodos propios.
 */
interface RepositoryInterface
{
    /**
     * Busca un registro por id. Devuelve null si no existe.
     */
    public function find(int $id): ?Model;

    /**
     * Listado paginado para admin. Filtros = pares columna => valor (where =).
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20, bool $withTrashed = false): LengthAwarePaginator;

    /**
     * Crea un registro con los datos indicados.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model;

    /**
     * Actualiza un registro existente y devuelve el modelo actualizado.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Model;

    /**
     * Elimina (soft-delete) un registro. Devuelve true si se eliminó.
     */
    public function delete(int $id): bool;

    /**
     * Restaura un registro previamente soft-deleted. Devuelve true si se restauró.
     */
    public function restore(int $id): bool;
}
