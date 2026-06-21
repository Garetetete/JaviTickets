<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Panel admin: consulta del registro de auditoría (append-only) generado
 * por el middleware audit.admin. Protegido por auth:admin + role:admin.
 */
class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $audit,
    ) {}

    /**
     * GET /admin/audit-logs — lista paginada de eventos de auditoría (?per_page).
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->audit->paginate((int) $request->integer('per_page', 20)));
    }
}
