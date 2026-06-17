<?php

namespace Tests\Feature\Services;

use App\DTOs\ValidateTicketData;
use App\Models\Customer;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\Tour;
use App\Services\QrService;
use App\Services\ValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeTicket(string $status = Ticket::STATUS_ACTIVE, string $orderStatus = Order::STATUS_VERIFIED): Ticket
    {
        $tour = Tour::create(['slug' => 't', 'name' => 'T', 'artist_name' => 'A']);
        $event = Event::create(['tour_id' => $tour->id, 'slug' => 'e', 'name' => 'E', 'capacity' => 100]);
        $type = TicketType::create([
            'tour_id' => $tour->id, 'event_id' => $event->id,
            'slug' => 'normal', 'name' => 'Normal', 'price' => 100,
        ]);
        $customer = Customer::create([
            'first_name' => 'Ana', 'last_name' => 'Pérez',
            'document_type' => 'dni', 'document_number' => '1', 'email' => 'a@e.com',
        ]);
        $order = Order::create([
            'customer_id' => $customer->id, 'payment_status' => $orderStatus,
            'amount' => 100, 'quantity' => 1, 'ticket_type_id' => $type->id, 'event_id' => $event->id,
        ]);

        $code = 'CODE-'.uniqid();

        return Ticket::create([
            'code' => $code,
            'qr_token' => app(QrService::class)->sign($code),
            'order_id' => $order->id, 'ticket_type_id' => $type->id,
            'event_id' => $event->id, 'customer_id' => $customer->id, 'status' => $status,
        ]);
    }

    private function service(): ValidationService
    {
        return app(ValidationService::class);
    }

    public function test_valid_ticket_is_marked_used_then_second_scan_is_already_used(): void
    {
        $ticket = $this->makeTicket();

        $first = $this->service()->validate(new ValidateTicketData($ticket->qr_token, gateUserId: null));
        $this->assertSame('valid', $first->result);
        $this->assertSame(Ticket::STATUS_USED, $ticket->refresh()->status);
        $this->assertNotNull($ticket->used_at);

        // Segundo escaneo (fuera de ventana anti-rebote -> already_used).
        config(['qr.scan_rebounce_seconds' => 0]);
        $second = $this->service()->validate(new ValidateTicketData($ticket->qr_token, gateUserId: null));
        $this->assertSame('already_used', $second->result);

        $this->assertDatabaseHas('scan_logs', ['code' => $ticket->code, 'result' => 'valid']);
        $this->assertDatabaseHas('scan_logs', ['code' => $ticket->code, 'result' => 'already_used']);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $result = $this->service()->validate(new ValidateTicketData('v1.bogus.signature'));

        $this->assertSame('invalid', $result->result);
        $this->assertDatabaseHas('scan_logs', ['result' => 'invalid_signature']);
    }

    public function test_unpaid_order_is_rejected(): void
    {
        $ticket = $this->makeTicket(orderStatus: Order::STATUS_PENDING_PAYMENT);

        $result = $this->service()->validate(new ValidateTicketData($ticket->qr_token));

        $this->assertSame('not_paid', $result->result);
        $this->assertSame(Ticket::STATUS_ACTIVE, $ticket->refresh()->status);
    }

    public function test_wrong_event_is_rejected(): void
    {
        $ticket = $this->makeTicket();

        $result = $this->service()->validate(new ValidateTicketData(
            $ticket->qr_token,
            expectedEventId: $ticket->event_id + 999,
        ));

        $this->assertSame('wrong_event', $result->result);
    }

    public function test_void_ticket_is_rejected(): void
    {
        $ticket = $this->makeTicket(status: Ticket::STATUS_VOID);

        $result = $this->service()->validate(new ValidateTicketData($ticket->qr_token));

        $this->assertSame('void', $result->result);
    }
}
