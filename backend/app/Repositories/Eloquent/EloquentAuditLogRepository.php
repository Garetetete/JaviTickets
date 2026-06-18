<?php

namespace App\Repositories\Eloquent;

use App\Models\AuditLog;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentAuditLogRepository implements AuditLogRepositoryInterface
{
    public function create(array $data): AuditLog
    {
        return AuditLog::query()->create($data);
    }

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return AuditLog::query()->latest('created_at')->paginate($perPage);
    }
}
