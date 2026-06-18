<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\ApiClient;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Recorre el ciclo COMPLETO contra los endpoints reales (todas las capas):
 * tienda compra -> webhook firmado -> emisión con aforo -> reconciliación ->
 * admin verifica métricas -> validación en puerta (anti-doble-entrada) ->
 * log de escaneos -> export CSV.
 */
class EndToEndFlowTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret = 'wh-secret';

    public function test_full_lifecycle(): void
    {
        // --- Seed mínimo ---
        ApiClient::create([
            'name' => 'Tienda', 'client_id' => 'shop', 'client_secret_hash' => Hash::make('s3cret'),
            'webhook_secret' => $this->webhookSecret, 'scopes' => ['orders:write', 'tickets:read'], 'is_active' => true,
        ]);
        AdminUser::create([
            'name' => 'Admin', 'email' => 'admin@e.com', 'password' => 'password', 'role' => 'admin', 'is_active' => true,
        ]);
        $tour = Tour::create(['slug' => 'cmt', 'name' => 'CHICA MALA TOUR', 'artist_name' => 'Dennis Fernando']);
        $event = Event::create(['tour_id' => $tour->id, 'slug' => 'bogota', 'name' => 'Bogotá', 'capacity' => 3]);
        $type = TicketType::create([
            'tour_id' => $tour->id, 'event_id' => $event->id, 'slug' => 'diamante', 'name' => 'Diamante', 'price' => 1000,
        ]);

        // --- 1) La tienda obtiene su token ---
        $clientToken = $this->postJson('/api/v1/client/token', [
            'client_id' => 'shop', 'client_secret' => 's3cret',
        ])->assertOk()->json('access_token');

        // --- 2) Crea la orden (idempotente) ---
        $this->withToken($clientToken)->postJson('/api/v1/orders', [
            'external_reference' => 'WP-100', 'event_id' => $event->id, 'ticket_type_id' => $type->id,
            'quantity' => 1, 'amount' => 1000,
            'customer' => ['first_name' => 'Ana', 'last_name' => 'Pérez', 'document_number' => '123', 'email' => 'ana@e.com'],
        ])->assertStatus(201)->assertJsonPath('payment_status', 'pending_payment');

        // --- 3) Webhook de pago firmado -> emite ticket ---
        $body = json_encode(['external_event_id' => 'evt-100', 'external_reference' => 'WP-100', 'status' => 'paid']);
        $ts = (string) now()->timestamp;
        $sig = hash_hmac('sha256', $ts.'.'.$body, $this->webhookSecret);

        $this->call('POST', '/api/v1/webhooks/payment', [], [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$clientToken}",
            'HTTP_X_SIGNATURE' => $sig, 'HTTP_X_TIMESTAMP' => $ts,
            'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
        ], $body)->assertOk()->assertJsonCount(1, 'tickets');

        // --- 4) Reconciliación: la tienda confronta su BD ---
        $qrToken = $this->withToken($clientToken)->getJson('/api/v1/orders/WP-100')
            ->assertOk()->assertJsonPath('payment_status', 'verified')
            ->json('tickets.0.qr_token');
        $this->assertNotEmpty($qrToken);

        // --- 5) Admin inicia sesión ---
        $adminToken = $this->postJson('/api/v1/admin/login', ['email' => 'admin@e.com', 'password' => 'password'])
            ->assertOk()->json('access_token');

        // --- 6) Métricas: 1 emitido, 0 usado ---
        $this->withToken($adminToken)->getJson("/api/v1/admin/dashboard/metrics?event_id={$event->id}")
            ->assertOk()
            ->assertJsonPath('overview.issued', 1)
            ->assertJsonPath('overview.used', 0)
            ->assertJsonPath('overview.available', 2);

        // --- 7) Validación en puerta: valid -> used ---
        $this->withToken($adminToken)->postJson('/api/v1/tickets/validate', ['qr_token' => $qrToken])
            ->assertOk()->assertJsonPath('result', 'valid');

        // --- 8) Segundo escaneo -> already_used ---
        config(['qr.scan_rebounce_seconds' => 0]);
        $this->withToken($adminToken)->postJson('/api/v1/tickets/validate', ['qr_token' => $qrToken])
            ->assertOk()->assertJsonPath('result', 'already_used');

        // --- 9) Métricas reflejan el uso ---
        $this->withToken($adminToken)->getJson("/api/v1/admin/dashboard/metrics?event_id={$event->id}")
            ->assertOk()->assertJsonPath('overview.used', 1);

        // --- 10) Log de escaneos: valid + already_used ---
        $this->withToken($adminToken)->getJson("/api/v1/admin/scans?event_id={$event->id}")
            ->assertOk()->assertJsonCount(2, 'data');

        // --- 11) Export CSV contiene el code del ticket ---
        $code = $this->withToken($clientToken)->getJson('/api/v1/orders/WP-100')->json('tickets.0.code');
        $response = $this->withToken($adminToken)->get("/api/v1/admin/tickets/export?event_id={$event->id}");
        $response->assertOk();
        $this->assertStringContainsString($code, $response->streamedContent());
    }
}
