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

    /**
     * Resuelve y valida el JWT de cliente: comprueba que sea de tipo `client`,
     * carga el ApiClient activo y lo expone en `$request->attributes` para los
     * middlewares y controladores siguientes.
     *
     * @param  \Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->bearerToken();
        if (! $raw) {
            return response()->json(['message' => 'Token ausente.'], 401);
        }

        try {
            $payload = JWTAuth::setToken($raw)->getPayload();
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
