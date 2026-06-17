<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Contrato base de CRUD + soft-delete/restore para agregados de negocio.
 * Las interfaces específicas extienden esta y añaden sus métodos propios.
 */
interface RepositoryInterface
{
    public function find(int $id): ?Model;

    public function create(array $data): Model;

    public function update(int $id, array $data): Model;

    public function delete(int $id): bool;

    public function restore(int $id): bool;
}
