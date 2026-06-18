<?php

namespace Tests\Feature\Api;

use App\Models\AdminUser;
use App\Models\Customer;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\Tour;
use App\Services\QrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationEndpointTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $tour = Tour::create(['slug' => 't', 'name' => 'T', 'artist_name' => 'A']);
        $this->event = Event::create(['tour_id' => $tour->id, 'slug' => 'e', 'name' => 'Bogotá', 'capacity' => 100]);
    }

    private function ticketFor(Event $event): Ticket
    {
        $type = TicketType::create([
            'tour_id' => $event->tour_id, 'event_id' => $event->id,
            'slug' => 'n'.$event->id, 'name' => 'Normal', 'price' => 100,
        ]);
        $customer = Customer::create([
            'first_name' => 'Ana', 'last_name' => 'Pérez',
            'document_type' => 'dni', 'document_number' => (string) $event->id, 'email' => "a{$event->id}@e.com",
        ]);
        $order = Order::create([
            'customer_id' => $customer->id, 'payment_status' => Order::STATUS_VERIFIED,
            'amount' => 100, 'quantity' => 1, 'ticket_type_id' => $type->id, 'event_id' => $event->id,
        ]);
        $code = 'CODE-'.uniqid();

        return Ticket::create([
            'code' => $code, 'qr_token' => app(QrService::class)->sign($code),
            'order_id' => $order->id, 'ticket_type_id' => $type->id,
            'event_id' => $event->id, 'customer_id' => $customer->id, 'status' => Ticket::STATUS_ACTIVE,
        ]);
    }

    private function tokenFor(AdminUser $user): string
    {
        return $this->postJson('/api/v1/admin/login', ['email' => $user->email, 'password' => 'password'])
            ->json('access_token');
    }

    private function gate(?int $eventId): AdminUser
    {
        return AdminUser::create([
            'name' => 'Gate', 'email' => 'gate'.($eventId ?? 'x').'@e.com', 'password' => 'password',
            'role' => 'gate', 'event_id' => $eventId, 'is_active' => true,
        ]);
    }

    public function test_requires_authentication(): void
    {
        $ticket = $this->ticketFor($this->event);
        $this->postJson('/api/v1/tickets/validate', ['qr_token' => $ticket->qr_token])->assertStatus(401);
    }

    public function test_gate_validates_then_second_scan_is_already_used(): void
    {
        $gate = $this->gate($this->event->id);
        $token = $this->tokenFor($gate);
        $ticket = $this->ticketFor($this->event);

        $this->withToken($token)->postJson('/api/v1/tickets/validate', ['qr_token' => $ticket->qr_token])
            ->assertOk()->assertJsonPath('result', 'valid');

        $this->assertSame(Ticket::STATUS_USED, $ticket->refresh()->status);

        config(['qr.scan_rebounce_seconds' => 0]);
        $this->withToken($token)->postJson('/api/v1/tickets/validate', ['qr_token' => $ticket->qr_token])
            ->assertOk()->assertJsonPath('result', 'already_used');
    }

    public function test_gate_cannot_validate_ticket_of_another_event(): void
    {
        $otherEvent = Event::create(['tour_id' => $this->event->tour_id, 'slug' => 'e2', 'name' => 'Lima', 'capacity' => 50]);
        $gate = $this->gate($this->event->id);
        $token = $this->tokenFor($gate);
        $ticket = $this->ticketFor($otherEvent);

        $this->withToken($token)->postJson('/api/v1/tickets/validate', ['qr_token' => $ticket->qr_token])
            ->assertOk()->assertJsonPath('result', 'wrong_event');

        $this->assertSame(Ticket::STATUS_ACTIVE, $ticket->refresh()->status);
    }

    public function test_admin_validates_any_event(): void
    {
        $admin = AdminUser::create([
            'name' => 'Admin', 'email' => 'admin@e.com', 'password' => 'password', 'role' => 'admin', 'is_active' => true,
        ]);
        $token = $this->tokenFor($admin);
        $ticket = $this->ticketFor($this->event);

        $this->withToken($token)->postJson('/api/v1/tickets/validate', ['qr_token' => $ticket->qr_token])
            ->assertOk()->assertJsonPath('result', 'valid');
    }

    public function test_invalid_token_is_reported(): void
    {
        $admin = AdminUser::create([
            'name' => 'Admin', 'email' => 'admin@e.com', 'password' => 'password', 'role' => 'admin', 'is_active' => true,
        ]);
        $token = $this->tokenFor($admin);

        $this->withToken($token)->postJson('/api/v1/tickets/validate', ['qr_token' => 'v1.bad.sig'])
            ->assertOk()->assertJsonPath('result', 'invalid');
    }
}
