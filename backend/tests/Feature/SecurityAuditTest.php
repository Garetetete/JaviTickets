<?php

namespace Tests\Feature;

use App\DTOs\OrderData;
use App\DTOs\ValidateTicketData;
use App\DTOs\WebhookPaymentData;
use App\Exceptions\CapacityExceededException;
use App\Exceptions\InvalidOrderException;
use App\Exceptions\SeatUnavailableException;
use App\Models\AdminUser;
use App\Models\ApiClient;
use App\Models\Customer;
use App\Models\Event;
use App\Models\Order;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\Tour;
use App\Services\PaymentService;
use App\Services\QrService;
use App\Services\ValidationService;
use App\Support\Qr\QrSigner;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Auditoría de seguridad: un test por propiedad declarada.
 */
class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    private Tour $tour;

    protected function setUp(): void
    {
        parent::setUp();
        cache()->flush(); // aísla el rate limiter entre tests

        $this->tour = Tour::create(['slug' => 't', 'name' => 'T', 'artist_name' => 'A']);
        ApiClient::create([
            'name' => 'WP', 'client_id' => 'wp', 'client_secret_hash' => Hash::make('secret123'),
            'webhook_secret' => 'wh-secret', 'scopes' => ['orders:write', 'tickets:read'], 'is_active' => true,
        ]);
        AdminUser::create(['name' => 'Admin', 'email' => 'admin@e.com', 'password' => 'password', 'role' => 'admin', 'is_active' => true]);
        AdminUser::create(['name' => 'Gate', 'email' => 'gate@e.com', 'password' => 'password', 'role' => 'gate', 'is_active' => true]);
    }

    // ---- helpers ----
    private function clientToken(): string
    {
        return $this->postJson('/api/v1/client/token', ['client_id' => 'wp', 'client_secret' => 'secret123'])->json('access_token');
    }

    private function token(string $email): string
    {
        return $this->postJson('/api/v1/admin/login', ['email' => $email, 'password' => 'password'])->json('access_token');
    }

    private function adminId(): int
    {
        return AdminUser::where('email', 'admin@e.com')->value('id');
    }

    private function event(string $seating = 'general', int $capacity = 50): Event
    {
        return Event::create([
            'tour_id' => $this->tour->id, 'slug' => 'e'.uniqid(), 'name' => 'E',
            'capacity' => $capacity, 'seating_type' => $seating,
        ]);
    }

    private function type(Event $e, float $price = 300, string $currency = 'USD', ?int $quota = null): TicketType
    {
        return TicketType::create([
            'tour_id' => $e->tour_id, 'event_id' => $e->id, 'slug' => 'n'.uniqid(),
            'name' => 'N', 'price' => $price, 'currency' => $currency, 'quota' => $quota,
        ]);
    }

    private function orderData(Event $e, TicketType $t, array $o = []): OrderData
    {
        return new OrderData(
            customer: ['first_name' => 'Ana', 'last_name' => 'P', 'document_type' => 'dni', 'document_number' => $o['doc'] ?? '1', 'email' => 'a@e.com'],
            eventId: $e->id, ticketTypeId: $t->id, quantity: $o['qty'] ?? 1,
            amount: $o['amount'] ?? (($o['qty'] ?? 1) * 300), currency: $o['currency'] ?? 'USD',
            externalReference: $o['ref'] ?? 'WP-'.uniqid(), seats: $o['seats'] ?? [],
        );
    }

    // 1) QR firmado + rotación de clave -----------------------------------
    public function test_qr_signed_tamper_rejected_and_rotation(): void
    {
        $qr = app(QrService::class);
        $token = $qr->sign('CODE-1');
        $this->assertTrue($qr->verify($token)->valid);
        $this->assertFalse($qr->verify(substr($token, 0, -2).'xy')->valid, 'token manipulado debe fallar');

        // Rotación: token firmado con v1 sigue validando aunque la actual sea v2.
        $signer = new QrSigner(['1' => 'old', '2' => 'new'], 2);
        $v1 = $signer->sign('CODE-OLD', 1);
        $this->assertTrue($signer->verify($v1)->valid);
        $this->assertSame(1, $signer->verify($v1)->keyVersion);
    }

    // 2) Anti-doble-entrada ------------------------------------------------
    public function test_anti_double_entry(): void
    {
        $e = $this->event(); $t = $this->type($e);
        $p = app(PaymentService::class);
        $o = $p->createOrder($this->orderData($e, $t));
        $ticket = $p->verifyManually($o->id, $this->adminId())->first();

        $v = app(ValidationService::class);
        $this->assertSame('valid', $v->validate(new ValidateTicketData($ticket->qr_token))->result);
        config(['qr.scan_rebounce_seconds' => 0]);
        $this->assertSame('already_used', $v->validate(new ValidateTicketData($ticket->qr_token))->result);
    }

    // 3) Anti-doble-venta: índice único parcial en BD ----------------------
    public function test_seat_partial_unique_index_blocks_duplicate_at_db_level(): void
    {
        $e = $this->event('seated'); $t = $this->type($e);
        $seat = Seat::create(['event_id' => $e->id, 'section' => 'GENERAL', 'label' => 'A-1']);
        $c = Customer::create(['first_name' => 'A', 'last_name' => 'B', 'document_number' => '9', 'email' => 'z@e.com']);
        $o = Order::create(['customer_id' => $c->id, 'payment_status' => 'verified', 'amount' => 300, 'quantity' => 1, 'ticket_type_id' => $t->id, 'event_id' => $e->id]);

        $base = ['qr_token' => 'x', 'order_id' => $o->id, 'ticket_type_id' => $t->id, 'event_id' => $e->id, 'customer_id' => $c->id, 'status' => 'active', 'seat_id' => $seat->id];
        Ticket::create($base + ['code' => 'C1']);

        $this->expectException(QueryException::class); // segundo ticket activo mismo asiento -> viola índice
        Ticket::create($base + ['code' => 'C2']);
    }

    // 3b) Anti-doble-venta vía servicio -----------------------------------
    public function test_seat_cannot_be_sold_twice_via_service(): void
    {
        $e = $this->event('seated'); $t = $this->type($e);
        Seat::create(['event_id' => $e->id, 'section' => 'GENERAL', 'label' => 'A-1']);
        $p = app(PaymentService::class);

        $o1 = $p->createOrder($this->orderData($e, $t, ['seats' => [['seat' => 'A-1']], 'doc' => '1', 'ref' => 'R1']));
        $p->verifyManually($o1->id, $this->adminId());

        $o2 = $p->createOrder($this->orderData($e, $t, ['seats' => [['seat' => 'A-1']], 'doc' => '2', 'ref' => 'R2']));
        $this->expectException(SeatUnavailableException::class);
        $p->verifyManually($o2->id, $this->adminId());
    }

    // 4) Aforo atómico -----------------------------------------------------
    public function test_capacity_never_oversells(): void
    {
        $e = $this->event('general', 2); $t = $this->type($e);
        $p = app(PaymentService::class);
        $o1 = $p->createOrder($this->orderData($e, $t, ['doc' => '1', 'ref' => 'A']));
        $o2 = $p->createOrder($this->orderData($e, $t, ['doc' => '2', 'ref' => 'B']));
        $o3 = $p->createOrder($this->orderData($e, $t, ['doc' => '3', 'ref' => 'C']));
        $p->verifyManually($o1->id, $this->adminId());
        $p->verifyManually($o2->id, $this->adminId());

        $this->expectException(CapacityExceededException::class);
        $p->verifyManually($o3->id, $this->adminId());
    }

    // 5) Idempotencia (orden + webhook) -----------------------------------
    public function test_idempotency_order_and_webhook(): void
    {
        $e = $this->event(); $t = $this->type($e);
        $p = app(PaymentService::class);

        $a = $p->createOrder($this->orderData($e, $t, ['ref' => 'DUP']));
        $b = $p->createOrder($this->orderData($e, $t, ['ref' => 'DUP']));
        $this->assertSame($a->id, $b->id);
        $this->assertDatabaseCount('orders', 1);

        $client = ApiClient::first();
        $data = new WebhookPaymentData('evt-1', 'DUP', 'paid', $client->id, ['s' => 'paid'], true);
        $p->handleWebhook($data);
        $p->handleWebhook($data); // mismo evento -> no re-emite
        $this->assertDatabaseCount('tickets', 1);
    }

    // 6) Secretos cifrados / hasheados ------------------------------------
    public function test_secrets_are_hashed_or_encrypted(): void
    {
        $client = ApiClient::where('client_id', 'wp')->first();
        $rawClient = DB::table('api_clients')->where('id', $client->id);
        $this->assertNotSame('secret123', $rawClient->value('client_secret_hash'));
        $this->assertTrue(Hash::check('secret123', $rawClient->value('client_secret_hash')));

        // webhook_secret cifrado en reposo
        $this->assertNotSame('wh-secret', $rawClient->value('webhook_secret'));
        $this->assertSame('wh-secret', $client->webhook_secret);

        // password admin hasheado
        $rawPass = DB::table('admin_users')->where('email', 'admin@e.com')->value('password');
        $this->assertNotSame('password', $rawPass);
        $this->assertTrue(Hash::check('password', $rawPass));
    }

    // 7) Validación server-side de monto y moneda -------------------------
    public function test_amount_and_currency_validated_server_side(): void
    {
        $e = $this->event(); $t = $this->type($e, 300, 'USD');
        $p = app(PaymentService::class);

        try {
            $p->createOrder($this->orderData($e, $t, ['amount' => 1, 'ref' => 'X1']));
            $this->fail('monto inválido debió rechazarse');
        } catch (InvalidOrderException) {
            $this->assertTrue(true);
        }

        $this->expectException(InvalidOrderException::class);
        $p->createOrder($this->orderData($e, $t, ['currency' => 'EUR', 'ref' => 'X2']));
    }

    // 8) Separación de credenciales (tests aislados: app fresca por método) --
    public function test_client_token_cannot_access_admin(): void
    {
        // token de cliente -> 401 limpio (middleware jwt.user lo rechaza antes del guard)
        $this->withToken($this->clientToken())->getJson('/api/v1/admin/tours')->assertStatus(401);
    }

    public function test_gate_token_forbidden_on_admin_routes(): void
    {
        $this->withToken($this->token('gate@e.com'))->getJson('/api/v1/admin/tours')->assertStatus(403);
    }

    public function test_gate_token_cannot_access_client_endpoints(): void
    {
        // ctype != client -> EnsureApiClient rechaza
        $this->withToken($this->token('gate@e.com'))->getJson('/api/v1/catalog/events')->assertStatus(401);
    }

    // 9) Auditoría ---------------------------------------------------------
    public function test_admin_actions_are_audited(): void
    {
        $e = $this->event(); $t = $this->type($e);
        $p = app(PaymentService::class);
        $o = $p->createOrder($this->orderData($e, $t, ['ref' => 'AUD']));

        $this->withToken($this->token('admin@e.com'))->postJson("/api/v1/admin/orders/{$o->id}/verify")->assertOk();

        $this->assertDatabaseHas('audit_logs', ['method' => 'POST', 'status_code' => 200]);
    }

    // 10) Throttle ---------------------------------------------------------
    public function test_login_is_rate_limited(): void
    {
        $last = 200;
        for ($i = 0; $i < 12; $i++) {
            $last = $this->postJson('/api/v1/admin/login', ['email' => 'admin@e.com', 'password' => 'bad'])->status();
        }
        $this->assertSame(429, $last, 'tras superar el límite debe devolver 429');
    }
}
