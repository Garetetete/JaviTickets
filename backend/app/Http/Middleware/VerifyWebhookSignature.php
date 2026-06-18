<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica la firma HMAC del webhook contra el webhook_secret del ApiClient.
 *  - Header X-Signature: hex(HMAC_SHA256("{X-Timestamp}.{rawBody}", webhook_secret))
 *  - Header X-Timestamp: epoch; se rechaza fuera de ventana (anti-replay).
 * Debe ejecutarse DESPUÉS de EnsureApiClient (necesita el api_client resuelto).
 */
class VerifyWebhookSignature
{
    private const REPLAY_WINDOW_SECONDS = 300;

    public function handle(Request $request, Closure $next): Response
    {
        /** @var ApiClient|null $client */
        $client = $request->attributes->get('api_client');
        $secret = $client?->webhook_secret;

        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');

        if (! $secret || ! $signature || ! $timestamp) {
            return response()->json(['message' => 'Firma de webhook ausente.'], 401);
        }

        if (abs(now()->timestamp - (int) $timestamp) > self::REPLAY_WINDOW_SECONDS) {
            return response()->json(['message' => 'Timestamp de webhook fuera de ventana.'], 401);
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        if (! hash_equals($expected, (string) $signature)) {
            return response()->json(['message' => 'Firma de webhook inválida.'], 401);
        }

        return $next($request);
    }
}
