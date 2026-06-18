<?php

namespace Tests\Feature\Api;

use App\DTOs\OrderData;
use App\Exceptions\CapacityExceededException;
use App\Exceptions\InvalidOrderException;
use App\Models\AdminUser;
use App\Models\ApiClient;
use App\Models\Customer;
use App\Models\Event;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\Tour;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImprovementsTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;
    private TicketType $type;

    protected function setUp(): void
    {
        parent::setUp();
        ApiClient::create([
            'name' => 'WP', 'client_id' => 'wp', 'client_secret_hash' => Hash::make('secret123'),
            'webhook_secret' => 'wh', 'scopes' => ['orders:write', 'tickets:read'], 'is_active' => true,
        ]);
        $tour = Tour::create(['slug' => 't', 'name' => 'T', 'artist_name' => 'A']);
        $this->event = Event::create(['tour_id' => $tour->id, 'slug' => 'e', 'name' => 'Bogotá', 'capacity' => 2]);
        $this->type = TicketType::create([
            'tour_id' => $tour->id, 'event_id' => $this->event->id, 'slug' => 'normal', 'name' => 'Normal', 'price' => 300,
        ]);
    }

    private function clientToken(): string
    {
        return $this->postJson('/api/v1/client/token', ['client_id' => 'wp', 'client_secret' => 'secret123'])
            ->json('access_token');
    }

    private function adminToken(): string
    {
        AdminUser::create(['name' => 'A', 'email' => 'admin@e.com', 'password' => 'password', 'role' => 'admin', 'is_active' => true]);

        return $this->postJson('/api/v1/admin/login', ['email' => 'admin@e.com', 'password' => 'password'])->json('access_token');
    }

    // #6 -------------------------------------------------------------------
    public function test_amount_mismatch_is_rejected(): void
    {
        $this->withToken($this->clientToken())->postJson('/api/v1/orders', [
            'external_reference' => 'WP-1', 'event_id' => $this->event->id, 'ticket_type_id' => $this->type->id,
            'quantity' => 1, 'amount' => 50, // debería ser 300
            'customer' => ['first_name' => 'A', 'last_name' => 'B', 'document_number' => '1', 'email' => 'a@e.com'],
        ])->assertStatus(422);
    }

    public function test_ticket_type_from_other_event_is_rejected(): void
    {
        $other = Event::create(['tour_id' => $this->event->tour_id, 'slug' => 'e2', 'name' => 'Lima', 'capacity' => 5]);

        $payments = app(PaymentService::class);
        $this->expectException(InvalidOrderException::class);
        $payments->createOrder(new OrderData(
            customer: ['first_name' => 'A', 'last_name' => 'B', 'document_number' => '1', 'email' => 'a@e.com'],
            eventId: $other->id, ticketTypeId: $this->type->id, quantity: 1, amount: 300,
        ));
    }

    // #7 -------------------------------------------------------------------
    public function test_catalog_endpoints_list_active(): void
    {
        $token = $this->clientToken();
        $this->withToken($token)->getJson('/api/v1/catalog/events')->assertOk()->assertJsonCount(1, 'data');
        $this->withToken($token)->getJson("/api/v1/catalog/ticket-types?event_id={$this->event->id}")
            ->assertOk()->assertJsonCount(1, 'data');
    }

    // #5 + #9 --------------------------------------------------------------
    public function test_receipt_upload_admin_download_and_audit(): void
    {
        Storage::fake('local');
        $ct = $this->clientToken();
        $orderId = $this->withToken($ct)->postJson('/api/v1/orders', [
            'external_reference' => 'WP-R', 'event_id' => $this->event->id, 'ticket_type_id' => $this->type->id,
            'quantity' => 1, 'amount' => 300,
            'customer' => ['first_name' => 'A', 'last_name' => 'B', 'document_number' => '1', 'email' => 'a@e.com'],
        ])->json('order_id');

        $this->withToken($ct)->post("/api/v1/orders/{$orderId}/receipt", [
            'file' => UploadedFile::fake()->create('comprobante.pdf', 20, 'application/pdf'),
        ])->assertOk();

        $at = $this->adminToken();
        $receiptId = $this->withToken($at)->getJson("/api/v1/admin/orders/{$orderId}/receipts")
            ->assertOk()->json('receipts.0.id');
        $this->assertNotNull($receiptId);

        $this->withToken($at)->get("/api/v1/admin/orders/{$orderId}/receipts/{$receiptId}/download")
            ->assertOk();

        // #9: verificar pago genera entrada de auditoría
        $this->withToken($at)->postJson("/api/v1/admin/orders/{$orderId}/verify")->assertOk();
        $this->assertDatabaseHas('audit_logs', ['method' => 'POST', 'status_code' => 200]);
        $this->withToken($at)->getJson('/api/v1/admin/audit-logs')->assertOk();
    }

    // #10 ------------------------------------------------------------------
    public function test_seats_are_assigned_on_issue(): void
    {
        $payments = app(PaymentService::class);
        $admin = AdminUser::create(['name' => 'A', 'email' => 'adm@e.com', 'password' => 'password', 'role' => 'admin', 'is_active' => true]);

        $order = $payments->createOrder(new OrderData(
            customer: ['first_name' => 'A', 'last_name' => 'B', 'document_number' => '1', 'email' => 'a@e.com'],
            eventId: $this->event->id, ticketTypeId: $this->type->id, quantity: 2, amount: 600,
            seats: [['section' => 'VIP', 'seat' => 'A-1'], ['section' => 'VIP', 'seat' => 'A-2']],
        ));

        $tickets = $payments->verifyManually($order->id, $admin->id);

        $this->assertEqualsCanonicalizing(['A-1', 'A-2'], $tickets->pluck('seat')->all());
        $this->assertSame('VIP', $tickets->first()->section);
    }

    // #11 ------------------------------------------------------------------
    public function test_aforo_does_not_oversell_across_orders(): void
    {
        $payments = app(PaymentService::class);
        $admin = AdminUser::create(['name' => 'A', 'email' => 'adm@e.com', 'password' => 'password', 'role' => 'admin', 'is_active' => true]);

        // capacity = 2; tres órdenes de 1 ticket cada una.
        $orders = [];
        foreach (['O1', 'O2', 'O3'] as $i => $ref) {
            $orders[] = $payments->createOrder(new OrderData(
                customer: ['first_name' => 'C', 'last_name' => (string) $i, 'document_number' => "d{$i}", 'email' => "c{$i}@e.com"],
                eventId: $this->event->id, ticketTypeId: $this->type->id, quantity: 1, amount: 300, externalReference: $ref,
            ));
        }

        $payments->verifyManually($orders[0]->id, $admin->id);
        $payments->verifyManually($orders[1]->id, $admin->id);

        // El tercero excede el aforo (2) -> excepción.
        $this->expectException(CapacityExceededException::class);
        $payments->verifyManually($orders[2]->id, $admin->id);

        $this->assertSame(2, \App\Models\Ticket::count());
    }
}
