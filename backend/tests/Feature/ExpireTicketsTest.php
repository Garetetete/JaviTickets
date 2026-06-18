<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ExpireTicketsTest extends TestCase
{
    use RefreshDatabase;

    public function test_expires_active_tickets_of_past_events_only(): void
    {
        $tour = Tour::create(['slug' => 't', 'name' => 'T', 'artist_name' => 'A']);
        $past = Event::create(['tour_id' => $tour->id, 'slug' => 'past', 'name' => 'Pasado', 'capacity' => 10, 'event_date' => now()->subDay()]);
        $future = Event::create(['tour_id' => $tour->id, 'slug' => 'fut', 'name' => 'Futuro', 'capacity' => 10, 'event_date' => now()->addDay()]);
        $type = TicketType::create(['tour_id' => $tour->id, 'slug' => 'n', 'name' => 'N', 'price' => 100]);
        $customer = Customer::create(['first_name' => 'A', 'last_name' => 'B', 'document_number' => '1', 'email' => 'a@e.com']);

        $mk = function (Event $e, string $code) use ($type, $customer) {
            $order = Order::create([
                'customer_id' => $customer->id, 'payment_status' => Order::STATUS_VERIFIED,
                'amount' => 100, 'quantity' => 1, 'ticket_type_id' => $type->id, 'event_id' => $e->id,
            ]);

            return Ticket::create([
                'code' => $code, 'qr_token' => 'tok-'.$code, 'order_id' => $order->id,
                'ticket_type_id' => $type->id, 'event_id' => $e->id, 'customer_id' => $customer->id,
                'status' => Ticket::STATUS_ACTIVE,
            ]);
        };

        $pastTicket = $mk($past, 'PAST');
        $futureTicket = $mk($future, 'FUT');

        Artisan::call('tickets:expire');

        $this->assertSame(Ticket::STATUS_EXPIRED, $pastTicket->refresh()->status);
        $this->assertSame(Ticket::STATUS_ACTIVE, $futureTicket->refresh()->status);
    }
}
