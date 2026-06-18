<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige que el usuario admin autenticado tenga uno de los roles indicados.
 * Uso: ->middleware(['auth:admin', 'role:admin']) o 'role:gate,admin'.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        /** @var AdminUser|null $user */
        $user = Auth::guard('admin')->user();

        if ($user === null || ! $user->is_active) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        if (! in_array($user->role, $roles, true)) {
            return response()->json(['message' => 'No tienes permiso para esta acción.'], 403);
        }

        return $next($request);
    }
}
