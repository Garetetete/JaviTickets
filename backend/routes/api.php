<?php

use App\Http\Controllers\Admin\ApiClientController as AdminApiClientController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ScanController;
use App\Http\Controllers\Admin\SeatController as AdminSeatController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\TicketTypeController as AdminTicketTypeController;
use App\Http\Controllers\Admin\TourController as AdminTourController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\CatalogController;
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

    // Catálogo para integradores (descubrir event_id / ticket_type_id, disponibilidad, asientos).
    Route::get('/catalog/tours', [CatalogController::class, 'tours'])->middleware('scope:tickets:read');
    Route::get('/catalog/events', [CatalogController::class, 'events'])->middleware('scope:tickets:read');
    Route::get('/catalog/ticket-types', [CatalogController::class, 'ticketTypes'])->middleware('scope:tickets:read');
    Route::get('/catalog/events/{id}/availability', [CatalogController::class, 'availability'])
        ->whereNumber('id')->middleware('scope:tickets:read');
    Route::get('/catalog/events/{id}/seats', [CatalogController::class, 'seats'])
        ->whereNumber('id')->middleware('scope:tickets:read');
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

/*
| Panel admin (solo rol admin).
*/
Route::middleware(['auth:admin', 'role:admin', 'audit.admin'])->prefix('admin')->group(function () {
    // Tours
    Route::get('tours', [AdminTourController::class, 'index']);
    Route::post('tours', [AdminTourController::class, 'store']);
    Route::get('tours/{id}', [AdminTourController::class, 'show'])->whereNumber('id');
    Route::put('tours/{id}', [AdminTourController::class, 'update'])->whereNumber('id');
    Route::delete('tours/{id}', [AdminTourController::class, 'destroy'])->whereNumber('id');
    Route::post('tours/{id}/restore', [AdminTourController::class, 'restore'])->whereNumber('id');

    // Events
    Route::get('events', [AdminEventController::class, 'index']);
    Route::post('events', [AdminEventController::class, 'store']);
    Route::get('events/{id}', [AdminEventController::class, 'show'])->whereNumber('id');
    Route::put('events/{id}', [AdminEventController::class, 'update'])->whereNumber('id');
    Route::delete('events/{id}', [AdminEventController::class, 'destroy'])->whereNumber('id');
    Route::post('events/{id}/restore', [AdminEventController::class, 'restore'])->whereNumber('id');

    // Ticket types
    Route::get('ticket-types', [AdminTicketTypeController::class, 'index']);
    Route::post('ticket-types', [AdminTicketTypeController::class, 'store']);
    Route::get('ticket-types/{id}', [AdminTicketTypeController::class, 'show'])->whereNumber('id');
    Route::put('ticket-types/{id}', [AdminTicketTypeController::class, 'update'])->whereNumber('id');
    Route::delete('ticket-types/{id}', [AdminTicketTypeController::class, 'destroy'])->whereNumber('id');
    Route::post('ticket-types/{id}/restore', [AdminTicketTypeController::class, 'restore'])->whereNumber('id');

    // Orders + verificación manual de pago
    Route::get('orders', [AdminOrderController::class, 'index']);
    Route::get('orders/{id}', [AdminOrderController::class, 'show'])->whereNumber('id');
    Route::post('orders/{id}/verify', [AdminOrderController::class, 'verify'])->whereNumber('id');
    Route::post('orders/{id}/reject', [AdminOrderController::class, 'reject'])->whereNumber('id');
    Route::get('orders/{id}/receipts', [AdminOrderController::class, 'receipts'])->whereNumber('id');
    Route::get('orders/{orderId}/receipts/{receiptId}/download', [AdminOrderController::class, 'downloadReceipt'])
        ->whereNumber('orderId')->whereNumber('receiptId');

    // Tickets
    Route::get('tickets', [AdminTicketController::class, 'index']);
    Route::get('tickets/export', [AdminTicketController::class, 'export']);
    Route::post('tickets/{id}/void', [AdminTicketController::class, 'void'])->whereNumber('id');
    Route::post('tickets/{id}/reissue', [AdminTicketController::class, 'reissue'])->whereNumber('id');

    // Asientos (inventario por evento, eventos numerados)
    Route::get('events/{id}/seats', [AdminSeatController::class, 'index'])->whereNumber('id');
    Route::post('events/{id}/seats', [AdminSeatController::class, 'store'])->whereNumber('id');
    Route::post('events/{id}/seats/generate', [AdminSeatController::class, 'generate'])->whereNumber('id');
    Route::delete('seats/{id}', [AdminSeatController::class, 'destroy'])->whereNumber('id');
    Route::post('seats/{id}/restore', [AdminSeatController::class, 'restore'])->whereNumber('id');

    // Scans + métricas + auditoría
    Route::get('scans', [ScanController::class, 'index']);
    Route::get('dashboard/metrics', [DashboardController::class, 'metrics']);
    Route::get('audit-logs', [AuditLogController::class, 'index']);

    // API clients
    Route::get('api-clients', [AdminApiClientController::class, 'index']);
    Route::post('api-clients', [AdminApiClientController::class, 'store']);
    Route::get('api-clients/{id}', [AdminApiClientController::class, 'show'])->whereNumber('id');
    Route::put('api-clients/{id}', [AdminApiClientController::class, 'update'])->whereNumber('id');
    Route::delete('api-clients/{id}', [AdminApiClientController::class, 'destroy'])->whereNumber('id');
    Route::post('api-clients/{id}/restore', [AdminApiClientController::class, 'restore'])->whereNumber('id');
    Route::post('api-clients/{id}/rotate-secret', [AdminApiClientController::class, 'rotateSecret'])->whereNumber('id');

    // Usuarios admin/gate
    Route::get('users', [AdminUserController::class, 'index']);
    Route::post('users', [AdminUserController::class, 'store']);
    Route::get('users/{id}', [AdminUserController::class, 'show'])->whereNumber('id');
    Route::put('users/{id}', [AdminUserController::class, 'update'])->whereNumber('id');
    Route::delete('users/{id}', [AdminUserController::class, 'destroy'])->whereNumber('id');
    Route::post('users/{id}/restore', [AdminUserController::class, 'restore'])->whereNumber('id');
});
