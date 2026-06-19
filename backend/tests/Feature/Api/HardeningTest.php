<?php

namespace Tests\Feature\Api;

use App\DTOs\OrderData;
use App\Exceptions\InvalidOrderException;
use App\Models\ApiClient;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\Tour;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;
    private TicketType $type;

    protected function setUp(): void
    {
        parent::setUp();
        $tour = Tour::create(['slug' => 't', 'name' => 'T', 'artist_name' => 'A']);
        $this->event = Event::create(['tour_id' => $tour->id, 'slug' => 'e', 'name' => 'E', 'capacity' => 50]);
        $this->type = TicketType::create([
            'tour_id' => $tour->id, 'event_id' => $this->event->id,
            'slug' => 'n', 'name' => 'N', 'price' => 300, 'currency' => 'USD',
        ]);
    }

    private function order(array $overrides = []): OrderData
    {
        return new OrderData(
            customer: array_merge([
                'first_name' => 'Ana', 'last_name' => 'Pérez',
                'document_type' => 'dni', 'document_number' => '123', 'email' => 'ANA@Example.com',
            ], $overrides['customer'] ?? []),
            eventId: $this->event->id, ticketTypeId: $this->type->id,
            quantity: $overrides['quantity'] ?? 1, amount: $overrides['amount'] ?? 300,
            currency: $overrides['currency'] ?? 'USD', externalReference: $overrides['ref'] ?? 'WP-1',
        );
    }

    public function test_webhook_secret_is_encrypted_at_rest(): void
    {
        $client = ApiClient::create([
            'name' => 'WP', 'client_id' => 'wp', 'client_secret_hash' => Hash::make('x'),
            'webhook_secret' => 'super-secret-value', 'scopes' => ['orders:write'], 'is_active' => true,
        ]);

        // En BD el valor está cifrado (no en claro)...
        $raw = DB::table('api_clients')->where('id', $client->id)->value('webhook_secret');
        $this->assertNotSame('super-secret-value', $raw);
        $this->assertNotEmpty($raw);

        // ...pero el modelo lo descifra al leer.
        $this->assertSame('super-secret-value', $client->fresh()->webhook_secret);
    }

    public function test_currency_mismatch_is_rejected(): void
    {
        $payments = app(PaymentService::class);
        $this->expectException(InvalidOrderException::class);
        $payments->createOrder($this->order(['currency' => 'EUR']));
    }

    public function test_same_person_is_one_customer_with_many_orders(): void
    {
        $payments = app(PaymentService::class);

        // Misma persona (mismo documento), distinto email/typo y dos compras.
        $payments->createOrder($this->order(['ref' => 'WP-1']));
        $payments->createOrder($this->order([
            'ref' => 'WP-2',
            'customer' => ['email' => 'ana@example.com'], // email distinto en mayúsc/minúsc
        ]));

        $this->assertDatabaseCount('customers', 1); // una sola identidad
        $this->assertDatabaseCount('orders', 2);     // pero dos compras
    }
}
