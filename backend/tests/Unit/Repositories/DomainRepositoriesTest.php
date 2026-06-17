<?php

namespace Tests\Unit\Repositories;

use App\Models\Customer;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\Tour;
use App\Repositories\Eloquent\EloquentApiClientRepository;
use App\Repositories\Eloquent\EloquentCustomerRepository;
use App\Repositories\Eloquent\EloquentEventRepository;
use App\Repositories\Eloquent\EloquentOrderRepository;
use App\Repositories\Eloquent\EloquentTicketRepository;
use App\Repositories\Eloquent\EloquentWebhookEventRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DomainRepositoriesTest extends TestCase
{
    use RefreshDatabase;

    /** Crea la cadena tour → event → ticket_type → customer → order. */
    private function scenario(): array
    {
        $tour = Tour::create(['slug' => 't', 'name' => 'T', 'artist_name' => 'A']);
        $event = Event::create([
            'tour_id' => $tour->id, 'slug' => 'e', 'name' => 'E', 'capacity' => 3,
        ]);
        $type = TicketType::create([
            'tour_id' => $tour->id, 'event_id' => $event->id,
            'slug' => 'normal', 'name' => 'Normal', 'price' => 100,
        ]);
        $customer = Customer::create([
            'first_name' => 'Ana', 'last_name' => 'Pérez',
            'document_type' => 'dni', 'document_number' => '123', 'email' => 'ana@example.com',
        ]);
        $order = Order::create([
            'customer_id' => $customer->id, 'payment_status' => Order::STATUS_VERIFIED,
            'amount' => 100, 'quantity' => 2, 'ticket_type_id' => $type->id,
            'event_id' => $event->id, 'external_reference' => 'WP-1',
        ]);

        return compact('tour', 'event', 'type', 'customer', 'order');
    }

    public function test_event_counts_issued_tickets_for_capacity(): void
    {
        $s = $this->scenario();
        $repo = new EloquentEventRepository;

        $this->assertSame(0, $repo->countIssuedTickets($s['event']->id));

        Ticket::create([
            'code' => 'C1', 'qr_token' => 'tok1', 'order_id' => $s['order']->id,
            'ticket_type_id' => $s['type']->id, 'event_id' => $s['event']->id,
            'customer_id' => $s['customer']->id, 'status' => Ticket::STATUS_ACTIVE,
        ]);
        Ticket::create([
            'code' => 'C2', 'qr_token' => 'tok2', 'order_id' => $s['order']->id,
            'ticket_type_id' => $s['type']->id, 'event_id' => $s['event']->id,
            'customer_id' => $s['customer']->id, 'status' => Ticket::STATUS_VOID,
        ]);

        // Solo cuenta issued|active|used (no void).
        $this->assertSame(1, $repo->countIssuedTickets($s['event']->id));
        $this->assertSame($s['event']->id, $repo->lockForIssue($s['event']->id)->id);
    }

    public function test_customer_first_or_create_is_idempotent_by_document(): void
    {
        $repo = new EloquentCustomerRepository;
        $data = [
            'first_name' => 'Luis', 'last_name' => 'Gómez',
            'document_type' => 'dni', 'document_number' => '999', 'email' => 'luis@example.com',
        ];

        $a = $repo->firstOrCreate($data);
        $b = $repo->firstOrCreate($data);

        $this->assertSame($a->id, $b->id);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_order_find_by_external_reference_and_mark_verified(): void
    {
        $s = $this->scenario();
        $repo = new EloquentOrderRepository;

        $this->assertSame($s['order']->id, $repo->findByExternalReference('WP-1')->id);
        $this->assertNull($repo->findByExternalReference('WP-NOPE'));

        $rejected = $repo->markRejected($s['order']->id, 'pago no válido');
        $this->assertSame(Order::STATUS_REJECTED, $rejected->payment_status);
        $this->assertSame('pago no válido', $rejected->rejected_reason);
    }

    public function test_ticket_create_many_and_find_by_code(): void
    {
        $s = $this->scenario();
        $repo = new EloquentTicketRepository;

        $rows = collect(['AAA', 'BBB'])->map(fn ($code) => [
            'code' => $code, 'qr_token' => 'tok-'.$code, 'order_id' => $s['order']->id,
            'ticket_type_id' => $s['type']->id, 'event_id' => $s['event']->id,
            'customer_id' => $s['customer']->id, 'status' => Ticket::STATUS_ACTIVE,
        ])->all();

        $created = $repo->createMany($rows);
        $this->assertCount(2, $created);
        $this->assertSame('AAA', $repo->findByCode('AAA')->code);

        $used = $repo->markUsed($repo->findByCode('AAA')->id, null);
        $this->assertSame(Ticket::STATUS_USED, $used->status);
        $this->assertNotNull($used->used_at);
    }

    public function test_webhook_event_idempotency_check(): void
    {
        $repo = new EloquentWebhookEventRepository;
        $client = (new EloquentApiClientRepository)->create([
            'name' => 'WP', 'client_id' => 'wp', 'client_secret_hash' => Hash::make('x'),
            'scopes' => ['orders:write'],
        ]);

        $this->assertFalse($repo->existsByExternalEventId('evt_1'));

        $repo->create([
            'api_client_id' => $client->id, 'external_event_id' => 'evt_1',
            'payload' => ['a' => 1], 'signature_valid' => true,
        ]);

        $this->assertTrue($repo->existsByExternalEventId('evt_1'));
    }

    public function test_api_client_find_and_rotate_secret(): void
    {
        $repo = new EloquentApiClientRepository;
        $client = $repo->create([
            'name' => 'WP', 'client_id' => 'wp-1', 'client_secret_hash' => Hash::make('old'),
            'scopes' => ['orders:write'],
        ]);

        $this->assertSame($client->id, $repo->findByClientId('wp-1')->id);

        $rotated = $repo->rotateSecret($client->id);
        $this->assertSame('wp-1', $rotated['client_id']);
        $this->assertNotEmpty($rotated['client_secret']);
        $this->assertTrue(Hash::check($rotated['client_secret'], $client->fresh()->client_secret_hash));
    }
}
