<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Panel admin: métricas del dashboard (resumen, ventas por tipo, ingresos y
 * resultados de escaneo) por evento. Protegido por auth:admin + role:admin.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly MetricsService $metrics,
    ) {}

    /**
     * GET /admin/dashboard/metrics — agrega las métricas de un evento
     * (overview, ventas por tipo, ingresos, resultados de escaneo).
     * 422 si falta el event_id requerido.
     */
    public function metrics(Request $request): JsonResponse
    {
        $request->validate(['event_id' => ['required', 'integer']]);
        $eventId = (int) $request->integer('event_id');

        return response()->json([
            'overview' => $this->metrics->eventOverview($eventId),
            'sales_by_type' => $this->metrics->salesByType($eventId),
            'revenue' => $this->metrics->revenue($eventId),
            'scan_results' => $this->metrics->scanResults($eventId),
        ]);
    }
}
