<?php

namespace Tests\Feature\Api;

use App\Models\ApiClient;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreFlowTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret = 'wh-secret';
    private Event $event;
    private TicketType $type;

    protected function setUp(): void
    {
        parent::setUp();

        ApiClient::create([
            'name' => 'WP',
            'client_id' => 'wp',
            'client_secret_hash' => Hash::make('secret123'),
            'webhook_secret' => $this->webhookSecret,
            'scopes' => ['orders:write', 'tickets:read'],
            'is_active' => true,
        ]);

        $tour = Tour::create(['slug' => 't', 'name' => 'T', 'artist_name' => 'A']);
        $this->event = Event::create(['tour_id' => $tour->id, 'slug' => 'e', 'name' => 'Bogotá', 'capacity' => 100]);
        $this->type = TicketType::create([
            'tour_id' => $tour->id, 'event_id' => $this->event->id,
            'slug' => 'normal', 'name' => 'Normal', 'price' => 300,
        ]);
    }

    private function token(): string
    {
        return $this->postJson('/api/v1/client/token', [
            'client_id' => 'wp',
            'client_secret' => 'secret123',
        ])->json('access_token');
    }

    private function orderPayload(string $ref = 'WP-1'): array
    {
        return [
            'external_reference' => $ref,
            'event_id' => $this->event->id,
            'ticket_type_id' => $this->type->id,
            'quantity' => 1,
            'amount' => 300,
            'customer' => [
                'first_name' => 'Ana', 'last_name' => 'Pérez',
                'document_type' => 'dni', 'document_number' => '123', 'email' => 'ana@e.com',
            ],
        ];
    }

    public function test_requires_authentication(): void
    {
        $this->postJson('/api/v1/orders', $this->orderPayload())->assertStatus(401);
    }

    public function test_invalid_credentials_rejected(): void
    {
        $this->postJson('/api/v1/client/token', [
            'client_id' => 'wp', 'client_secret' => 'wrong',
        ])->assertStatus(401);
    }

    public function test_create_order_is_idempotent(): void
    {
        $token = $this->token();

        $this->withToken($token)->postJson('/api/v1/orders', $this->orderPayload())
            ->assertStatus(201)
            ->assertJsonPath('payment_status', 'pending_payment');

        // Misma referencia -> idempotente (200, no duplica).
        $this->withToken($token)->postJson('/api/v1/orders', $this->orderPayload())
            ->assertStatus(200);

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_manual_receipt_upload_transitions_state(): void
    {
        Storage::fake('local');
        $token = $this->token();

        $orderId = $this->withToken($token)->postJson('/api/v1/orders', $this->orderPayload())->json('order_id');

        $this->withToken($token)->post(
            "/api/v1/orders/{$orderId}/receipt",
            ['file' => UploadedFile::fake()->create('r.pdf', 50, 'application/pdf')]
        )->assertOk()->assertJsonPath('payment_status', 'pending_verification');

        $this->assertDatabaseHas('payment_receipts', ['order_id' => $orderId]);
    }

    public function test_webhook_issues_tickets_and_reconciles(): void
    {
        $token = $this->token();
        $this->withToken($token)->postJson('/api/v1/orders', $this->orderPayload('WP-9'))->assertStatus(201);

        $body = json_encode([
            'external_event_id' => 'evt_1',
            'external_reference' => 'WP-9',
            'status' => 'paid',
        ]);
        $ts = (string) now()->timestamp;
        $sig = hash_hmac('sha256', $ts.'.'.$body, $this->webhookSecret);

        $response = $this->call('POST', '/api/v1/webhooks/payment', [], [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$token}",
            'HTTP_X_SIGNATURE' => $sig,
            'HTTP_X_TIMESTAMP' => $ts,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $body);

        $response->assertOk()->assertJsonCount(1, 'tickets');
        $this->assertDatabaseCount('tickets', 1);

        // Reconciliación.
        $this->withToken($token)->getJson('/api/v1/orders/WP-9')
            ->assertOk()
            ->assertJsonPath('payment_status', 'verified')
            ->assertJsonCount(1, 'tickets');
    }

    public function test_webhook_with_bad_signature_is_rejected(): void
    {
        $token = $this->token();
        $this->withToken($token)->postJson('/api/v1/orders', $this->orderPayload('WP-5'))->assertStatus(201);

        $body = json_encode(['external_event_id' => 'evt_x', 'external_reference' => 'WP-5', 'status' => 'paid']);
        $ts = (string) now()->timestamp;

        $this->call('POST', '/api/v1/webhooks/payment', [], [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$token}",
            'HTTP_X_SIGNATURE' => 'deadbeef',
            'HTTP_X_TIMESTAMP' => $ts,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $body)->assertStatus(401);
    }

    public function test_verify_endpoint_reports_validity(): void
    {
        $token = $this->token();
        $this->withToken($token)->postJson('/api/v1/orders', $this->orderPayload('WP-7'))->assertStatus(201);

        $body = json_encode(['external_event_id' => 'evt_7', 'external_reference' => 'WP-7', 'status' => 'paid']);
        $ts = (string) now()->timestamp;
        $sig = hash_hmac('sha256', $ts.'.'.$body, $this->webhookSecret);
        $code = $this->call('POST', '/api/v1/webhooks/payment', [], [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$token}",
            'HTTP_X_SIGNATURE' => $sig,
            'HTTP_X_TIMESTAMP' => $ts,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $body)->json('tickets.0.code');

        $this->withToken($token)->getJson("/api/v1/tickets/{$code}/verify")
            ->assertOk()
            ->assertJsonPath('valid', true);
    }
}
