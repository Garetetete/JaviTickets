<?php

namespace Tests\Feature\Services;

use App\DTOs\OrderData;
use App\DTOs\ReceiptData;
use App\DTOs\WebhookPaymentData;
use App\Models\AdminUser;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\Tour;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private TicketType $type;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $tour = Tour::create(['slug' => 't', 'name' => 'T', 'artist_name' => 'A']);
        $this->event = Event::create(['tour_id' => $tour->id, 'slug' => 'e', 'name' => 'E', 'capacity' => 100]);
        $this->type = TicketType::create([
            'tour_id' => $tour->id, 'event_id' => $this->event->id,
            'slug' => 'normal', 'name' => 'Normal', 'price' => 300,
        ]);
    }

    private function orderData(string $ref = 'WP-1'): OrderData
    {
        return new OrderData(
            customer: [
                'first_name' => 'Ana', 'last_name' => 'Pérez',
                'document_type' => 'dni', 'document_number' => '123', 'email' => 'ana@e.com',
            ],
            eventId: $this->event->id,
            ticketTypeId: $this->type->id,
            quantity: 1,
            amount: 300,
            externalReference: $ref,
        );
    }

    private function service(): PaymentService
    {
        return app(PaymentService::class);
    }

    public function test_create_order_is_idempotent_by_external_reference(): void
    {
        $a = $this->service()->createOrder($this->orderData('WP-1'));
        $b = $this->service()->createOrder($this->orderData('WP-1'));

        $this->assertSame($a->id, $b->id);
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame('pending_payment', $a->payment_status);
    }

    public function test_manual_flow_receipt_then_verify_issues_tickets(): void
    {
        $admin = AdminUser::create([
            'name' => 'Admin', 'email' => 'admin@e.com', 'password' => 'x', 'role' => 'admin',
        ]);

        $order = $this->service()->createOrder($this->orderData());
        $afterReceipt = $this->service()->attachReceipt($order->id, new ReceiptData('receipts/x.png'));
        $this->assertSame('pending_verification', $afterReceipt->payment_status);

        $tickets = $this->service()->verifyManually($order->id, $admin->id);

        $this->assertCount(1, $tickets);
        $this->assertSame('verified', $order->refresh()->payment_status);
        $this->assertDatabaseHas('payment_receipts', ['order_id' => $order->id]);
    }

    public function test_webhook_flow_issues_and_is_idempotent(): void
    {
        $client = \App\Models\ApiClient::create([
            'name' => 'WP', 'client_id' => 'wp', 'client_secret_hash' => 'x',
            'scopes' => ['orders:write'],
        ]);

        $order = $this->service()->createOrder($this->orderData('WP-9'));

        $data = new WebhookPaymentData(
            externalEventId: 'evt_1',
            externalReference: 'WP-9',
            status: 'paid',
            apiClientId: $client->id,
            payload: ['status' => 'paid'],
            signatureValid: true,
        );

        $first = $this->service()->handleWebhook($data);
        $second = $this->service()->handleWebhook($data); // mismo evento -> idempotente

        $this->assertCount(1, $first);
        $this->assertCount(1, $second);
        $this->assertDatabaseCount('tickets', 1);
        $this->assertSame('verified', $order->refresh()->payment_status);
    }
}
