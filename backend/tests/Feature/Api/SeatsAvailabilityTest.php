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
        $this->event = Event::create(['tour_id' => $tour->id, 'slug' => 'e', 'name' => 'Bogotá', 'capacity' => 5]);
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
}
