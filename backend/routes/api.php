<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Api\ClientAuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\ValidationController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1  (prefijo "api/v1" definido en bootstrap/app.php)
|--------------------------------------------------------------------------
| Audiencias (ver docs/specs/api/endpoints.md):
|   - Consumido por la tienda (auth.api_client, JWT cliente + scopes)  -> abajo
|   - Validación en puerta (auth gate)      -> Fase 6
|   - Panel admin (auth admin)              -> Fase 6/7
*/

Route::get('/up', fn () => response()->json([
    'status' => 'ok',
    'service' => config('app.name'),
    'time' => now()->toIso8601String(),
]));

// Client-credentials: la tienda obtiene su token.
Route::post('/client/token', [ClientAuthController::class, 'token'])
    ->middleware('throttle:10,1');

// Endpoints consumidos por la tienda / clientes máquina.
Route::middleware(['auth.api_client', 'throttle:120,1'])->group(function () {
    Route::post('/orders', [OrderController::class, 'store'])
        ->middleware('scope:orders:write');

    Route::post('/orders/{id}/receipt', [OrderController::class, 'receipt'])
        ->whereNumber('id')
        ->middleware('scope:orders:write');

    Route::get('/orders/{externalReference}', [OrderController::class, 'show'])
        ->middleware('scope:tickets:read');

    Route::post('/webhooks/payment', [WebhookController::class, 'payment'])
        ->middleware(['scope:orders:write', 'verify.webhook']);

    Route::get('/tickets/{code}/verify', [TicketController::class, 'verify'])
        ->middleware('scope:tickets:read');

    Route::get('/tickets/{code}/image', [TicketController::class, 'image'])
        ->middleware('scope:tickets:read');
});

/*
| Auth admin/gate (JWT, guard "admin").
*/
Route::post('/admin/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/me', [AuthController::class, 'me']);
    Route::post('/admin/logout', [AuthController::class, 'logout']);
    Route::post('/admin/refresh', [AuthController::class, 'refresh']);

    // Validación en puerta: gate (su evento) o admin (cualquiera).
    Route::post('/tickets/validate', [ValidationController::class, 'validateTicket'])
        ->middleware('role:gate,admin');
});
