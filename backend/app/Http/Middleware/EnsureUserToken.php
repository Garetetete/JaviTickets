<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Rechaza tokens de cliente máquina (ctype=client) en rutas de usuario
 * (admin/gate), ANTES del guard JWT, para devolver 401 limpio en lugar de
 * que el guard intente resolver un sub no numérico y rompa.
 */
class EnsureUserToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->bearerToken();
        if (! $raw) {
            return response()->json(['message' => 'Token ausente.'], 401);
        }

        try {
            $payload = JWTAuth::setToken($raw)->getPayload();
        } catch (Throwable) {
            return response()->json(['message' => 'Token inválido.'], 401);
        }

        // Los usuarios admin/gate tienen sub numérico (id); el cliente máquina
        // tiene client_id (no numérico, garantizado por validación). Rechazamos
        // cualquier token cuyo subject no sea un id de usuario.
        if (! is_numeric($payload->get('sub'))) {
            return response()->json(['message' => 'Token no válido para esta ruta.'], 401);
        }

        return $next($request);
    }
}
