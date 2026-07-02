<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Rutas de infraestructura (health check + raíz). Viven en un controlador, no
 * en closures, para que `php artisan route:cache` funcione en producción
 * (Laravel no puede cachear rutas basadas en Closure).
 */
class HealthController extends Controller
{
    /**
     * Health check de la API: GET /api/v1/up.
     */
    public function up(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => config('app.name'),
            'time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Página raíz (welcome por defecto): GET /.
     */
    public function welcome(): View
    {
        return view('welcome');
    }
}
