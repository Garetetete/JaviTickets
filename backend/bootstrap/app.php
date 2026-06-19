<?php

use App\Exceptions\DomainException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth.api_client' => \App\Http\Middleware\EnsureApiClient::class,
            'scope' => \App\Http\Middleware\EnsureScope::class,
            'verify.webhook' => \App\Http\Middleware\VerifyWebhookSignature::class,
            'role' => \App\Http\Middleware\EnsureRole::class,
            'audit.admin' => \App\Http\Middleware\AuditAdminActions::class,
            'jwt.user' => \App\Http\Middleware\EnsureUserToken::class,
        ]);

        // Asegura que jwt.user corra ANTES que el guard Authenticate (que se
        // ordena por prioridad), para rechazar tokens de cliente sin tocar el guard.
        $middleware->priority([
            \App\Http\Middleware\EnsureUserToken::class,
            \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,
            \Illuminate\Contracts\Session\Middleware\AuthenticatesSessions::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Illuminate\Auth\Middleware\Authorize::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Traduce excepciones de dominio a JSON con su status HTTP.
        $exceptions->render(function (DomainException $e, Request $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => $e->errorCode(),
            ], $e->status());
        });
    })->create();
