<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->audit->paginate((int) $request->integer('per_page', 20)));
    }
}
