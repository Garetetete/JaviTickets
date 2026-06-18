<?php

namespace App\Http\Middleware;

use App\Repositories\Contracts\ApiClientRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Autentica al cliente máquina (la tienda) por su JWT client-credentials.
 * Resuelve el ApiClient y lo deja en los atributos del request.
 */
class EnsureApiClient
{
    public function __construct(
        private readonly ApiClientRepositoryInterface $apiClients,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $payload = JWTAuth::parseToken()->getPayload();
        } catch (Throwable) {
            return response()->json(['message' => 'Token ausente o inválido.'], 401);
        }

        if ($payload->get('ctype') !== 'client') {
            return response()->json(['message' => 'Token no es de cliente.'], 401);
        }

        $client = $this->apiClients->findByClientId((string) $payload->get('sub'));

        if ($client === null || ! $client->is_active) {
            return response()->json(['message' => 'Cliente no autorizado.'], 401);
        }

        $request->attributes->set('api_client', $client);

        return $next($request);
    }
}
