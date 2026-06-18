<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige que el ApiClient autenticado tenga el scope indicado.
 * Uso en rutas: ->middleware('scope:orders:write')
 */
class EnsureScope
{
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        /** @var ApiClient|null $client */
        $client = $request->attributes->get('api_client');

        if ($client === null || ! $client->hasScope($scope)) {
            return response()->json(['message' => "Falta el scope requerido: {$scope}."], 403);
        }

        return $next($request);
    }
}
