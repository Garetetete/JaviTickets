<?php

namespace Tests\Feature\Api;

use App\DTOs\OrderData;
use App\Exceptions\SeatUnavailableException;
use App\Models\AdminUser;
use App\Models\ApiClient;
use App\Models\Event;
use App\Models\Seat;
use App\Models\TicketType;
use App\Models\Tour;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeatsAvailabilityTest extends TestCase
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
        $this->event = Event::create(['tour_id' => $tour->id, 'slug' => 'e', 'name' => 'Bogotá', 'capacity' => 5, 'seating_type' => 'seated']);
        $this->type = TicketType::create([
            'tour_id' => $tour->id, 'event_id' => $this->event->id, 'slug' => 'normal', 'name' => 'Normal', 'price' => 300,
        ]);
    }

    private function clientToken(): string
    {
        return $this->postJson('/api/v1/client/token', ['client_id' => 'wp', 'client_secret' => 'secret123'])
            ->json('access_token');
    }

    private function admin(): AdminUser
    {
        return AdminUser::create(['name' => 'A', 'email' => 'a@e.com', 'password' => 'password', 'role' => 'admin', 'is_active' => true]);
    }

    private function order(array $seats = [], int $qty = 1, string $ref = 'WP-1'): OrderData
    {
        return new OrderData(
            customer: ['first_name' => 'A', 'last_name' => 'B', 'document_number' => '1', 'email' => 'a@e.com'],
            eventId: $this->event->id, ticketTypeId: $this->type->id, quantity: $qty, amount: 300 * $qty,
            externalReference: $ref, seats: $seats,
        );
    }

    // #1 -------------------------------------------------------------------
    public function test_availability_reflects_sales(): void
    {
        $token = $this->clientToken();

        $this->withToken($token)->getJson("/api/v1/catalog/events/{$this->event->id}/availability")
            ->assertOk()
            ->assertJsonPath('capacity', 5)
            ->assertJsonPath('sold', 0)
            ->assertJsonPath('available', 5)
            ->assertJsonPath('sold_out', false);

        // Vender 1
        $payments = app(PaymentService::class);
        $o = $payments->createOrder($this->order(ref: 'WP-A'));
        $payments->verifyManually($o->id, $this->admin()->id);

        $this->withToken($token)->getJson("/api/v1/catalog/events/{$this->event->id}/availability")
            ->assertOk()
            ->assertJsonPath('sold', 1)
            ->assertJsonPath('available', 4)
            ->assertJsonPath('by_type.0.sold', 1)
            ->assertJsonPath('by_type.0.available', 4);
    }

    // #2 -------------------------------------------------------------------
    public function test_seat_is_assigned_and_marked_taken(): void
    {
        Seat::create(['event_id' => $this->event->id, 'section' => 'GENERAL', 'label' => 'A-1']);
        $payments = app(PaymentService::class);

        $o = $payments->createOrder($this->order(seats: [['seat' => 'A-1']], ref: 'WP-S1'));
        $tickets = $payments->verifyManually($o->id, $this->admin()->id);

        $this->assertSame('A-1', $tickets->first()->seat);
        $this->assertNotNull($tickets->first()->seat_id);

        // El catálogo de asientos lo marca ocupado.
        $this->withToken($this->clientToken())->getJson("/api/v1/catalog/events/{$this->event->id}/seats")
            ->assertOk()
            ->assertJsonPath('seats.0.label', 'A-1')
            ->assertJsonPath('seats.0.available', false);
    }

    public function test_seat_cannot_be_sold_twice(): void
    {
        Seat::create(['event_id' => $this->event->id, 'section' => 'GENERAL', 'label' => 'A-1']);
        $payments = app(PaymentService::class);
        $admin = $this->admin();

        $o1 = $payments->createOrder($this->order(seats: [['seat' => 'A-1']], ref: 'WP-1'));
        $payments->verifyManually($o1->id, $admin->id);

        $o2 = $payments->createOrder($this->order(seats: [['seat' => 'A-1']], ref: 'WP-2'));

        $this->expectException(SeatUnavailableException::class);
        $payments->verifyManually($o2->id, $admin->id);
    }

    public function test_unknown_seat_is_rejected_when_inventory_exists(): void
    {
        Seat::create(['event_id' => $this->event->id, 'section' => 'GENERAL', 'label' => 'A-1']);
        $payments = app(PaymentService::class);

        $o = $payments->createOrder($this->order(seats: [['seat' => 'Z-99']], ref: 'WP-Z'));

        $this->expectException(SeatUnavailableException::class);
        $payments->verifyManually($o->id, $this->admin()->id);
    }

    // Doble-clic: misma external_reference -> una sola orden
    public function test_double_submit_same_reference_creates_one_order(): void
    {
        $payments = app(PaymentService::class);
        $a = $payments->createOrder($this->order(ref: 'WP-DUP'));
        $b = $payments->createOrder($this->order(ref: 'WP-DUP'));

        $this->assertSame($a->id, $b->id);
        $this->assertDatabaseCount('orders', 1);
    }

    // Tipo de evento -------------------------------------------------------
    public function test_general_admission_event_rejects_seats(): void
    {
        $general = Event::create([
            'tour_id' => $this->event->tour_id, 'slug' => 'gen', 'name' => 'GA',
            'capacity' => 100, 'seating_type' => 'general',
        ]);
        $type = TicketType::create([
            'tour_id' => $general->tour_id, 'event_id' => $general->id, 'slug' => 'ga', 'name' => 'GA', 'price' => 100,
        ]);

        $payments = app(PaymentService::class);
        $this->expectException(\App\Exceptions\InvalidOrderException::class);
        $payments->createOrder(new OrderData(
            customer: ['first_name' => 'A', 'last_name' => 'B', 'document_number' => '1', 'email' => 'a@e.com'],
            eventId: $general->id, ticketTypeId: $type->id, quantity: 1, amount: 100,
            seats: [['seat' => 'A-1']],
        ));
    }

    // Generador por rango --------------------------------------------------
    public function test_admin_generates_seats_by_range(): void
    {
        AdminUser::create(['name' => 'A', 'email' => 'adm@e.com', 'password' => 'password', 'role' => 'admin', 'is_active' => true]);
        $token = $this->postJson('/api/v1/admin/login', ['email' => 'adm@e.com', 'password' => 'password'])->json('access_token');

        // Filas A..C × asientos 1..10 = 30 asientos en sección PLATEA.
        $this->withToken($token)->postJson("/api/v1/admin/events/{$this->event->id}/seats/generate", [
            'section' => 'PLATEA', 'row_from' => 'A', 'row_to' => 'C', 'seat_from' => 1, 'seat_to' => 10,
        ])->assertStatus(201)->assertJsonPath('created', 30);

        $this->assertDatabaseCount('seats', 30);
        $this->assertDatabaseHas('seats', ['event_id' => $this->event->id, 'section' => 'PLATEA', 'label' => 'A-1']);
        $this->assertDatabaseHas('seats', ['event_id' => $this->event->id, 'section' => 'PLATEA', 'label' => 'C-10']);
    }
}
