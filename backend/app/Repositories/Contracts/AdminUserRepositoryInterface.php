<?php

namespace App\Repositories\Contracts;

use App\Models\AdminUser;

interface AdminUserRepositoryInterface extends RepositoryInterface
{
    public function findByEmail(string $email): ?AdminUser;
}
