<?php

namespace App\Repositories\Contracts;

use App\Models\AdminUser;

interface AdminUserRepositoryInterface extends RepositoryInterface
{
    /**
     * Busca un usuario operador por email. Null si no existe.
     */
    public function findByEmail(string $email): ?AdminUser;
}
