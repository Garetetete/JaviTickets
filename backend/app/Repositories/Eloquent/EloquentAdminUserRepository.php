<?php

namespace App\Repositories\Eloquent;

use App\Models\AdminUser;
use App\Repositories\Contracts\AdminUserRepositoryInterface;

class EloquentAdminUserRepository extends EloquentRepository implements AdminUserRepositoryInterface
{
    protected string $model = AdminUser::class;

    public function findByEmail(string $email): ?AdminUser
    {
        return AdminUser::query()->where('email', $email)->first();
    }
}
