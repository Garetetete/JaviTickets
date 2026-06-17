<?php

namespace Tests\Feature\Services;

use App\Exceptions\CapacityExceededException;
use App\Models\Customer;
use App\Models\Event;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\Tour;
use App\Services\QrService;
use App\Services\TicketIssuanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketIssuanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(int $capacity, int $quantity, ?int $quota = null): Order
    {
        $tour = Tour::create(['slug' => 't', 'name' => 'T', 'artist_name' => 'A']);
        $event = Event::create(['tour_id' => $tour->id, 'slug' => 'e', 'name' => 'E', 'capacity' => $capacity]);
        $type = TicketType::create([
            'tour_id' => $tour->id, 'event_id' => $event->id,
            'slug' => 'normal', 'name' => 'Normal', 'price' => 100, 'quota' => $quota,
        ]);
        $customer = Customer::create([
            'first_name' => 'Ana', 'last_name' => 'Pérez',
            'document_type' => 'dni', 'document_number' => '1', 'email' => 'a@e.com',
        ]);

        return Order::create([
            'customer_id' => $customer->id, 'payment_status' => Order::STATUS_VERIFIED,
            'amount' => 200, 'quantity' => $quantity, 'ticket_type_id' => $type->id,
            'event_id' => $event->id,
        ]);
    }

    private function service(): TicketIssuanceService
    {
        return app(TicketIssuanceService::class);
    }

    public function test_issues_tickets_with_unique_signed_codes(): void
    {
        $order = $this->makeOrder(capacity: 5, quantity: 2);

        $tickets = $this->service()->issueForOrder($order);

        $this->assertCount(2, $tickets);
        $this->assertCount(2, $tickets->pluck('code')->unique());

        $qr = app(QrService::class);
        foreach ($tickets as $ticket) {
            $verify = $qr->verify($ticket->qr_token);
            $this->assertTrue($verify->valid);
            $this->assertSame($ticket->code, $verify->code);
        }
    }

    public function test_throws_when_event_capacity_exceeded(): void
    {
        $order = $this->makeOrder(capacity: 1, quantity: 2);

        $this->expectException(CapacityExceededException::class);
        $this->service()->issueForOrder($order);
    }

    public function test_throws_when_type_quota_exceeded(): void
    {
        $order = $this->makeOrder(capacity: 100, quantity: 2, quota: 1);

        $this->expectException(CapacityExceededException::class);
        $this->service()->issueForOrder($order);
    }

    public function test_issue_is_idempotent(): void
    {
        $order = $this->makeOrder(capacity: 5, quantity: 2);

        $first = $this->service()->issueForOrder($order);
        $second = $this->service()->issueForOrder($order->refresh());

        $this->assertCount(2, $first);
        $this->assertCount(2, $second);
        $this->assertDatabaseCount('tickets', 2);
    }
}
