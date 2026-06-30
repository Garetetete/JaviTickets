<?php

namespace App\Repositories\Eloquent;

use App\Models\AdminUser;
use App\Repositories\Contracts\AdminUserRepositoryInterface;

/**
 * Implementación Eloquent de {@see \App\Repositories\Contracts\AdminUserRepositoryInterface}.
 * Es la ÚNICA capa autorizada a ejecutar consultas Eloquent sobre el modelo AdminUser.
 */
class EloquentAdminUserRepository extends EloquentRepository implements AdminUserRepositoryInterface
{
    /** Modelo Eloquent gestionado por este repositorio. */
    protected string $model = AdminUser::class;

    /**
     * {@inheritDoc}
     *
     * Consulta el primer AdminUser cuyo email coincida.
     */
    public function findByEmail(string $email): ?AdminUser
    {
        return AdminUser::query()->where('email', $email)->first();
    }
}
