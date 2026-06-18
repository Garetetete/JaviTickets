<?php

namespace Tests\Feature\Api;

use App\Models\AdminUser;
use App\Models\Customer;
use App\Models\Event;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        AdminUser::create([
            'name' => 'Admin', 'email' => 'admin@e.com', 'password' => 'password',
            'role' => 'admin', 'is_active' => true,
        ]);

        return $this->postJson('/api/v1/admin/login', ['email' => 'admin@e.com', 'password' => 'password'])
            ->json('access_token');
    }

    private function gateToken(): string
    {
        AdminUser::create([
            'name' => 'Gate', 'email' => 'gate@e.com', 'password' => 'password',
            'role' => 'gate', 'is_active' => true,
        ]);

        return $this->postJson('/api/v1/admin/login', ['email' => 'gate@e.com', 'password' => 'password'])
            ->json('access_token');
    }

    public function test_admin_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/tours')->assertStatus(401);
    }

    public function test_gate_role_is_forbidden_from_admin_routes(): void
    {
        $this->withToken($this->gateToken())->getJson('/api/v1/admin/tours')->assertStatus(403);
    }

    public function test_tour_crud_with_soft_delete_and_restore(): void
    {
        $token = $this->adminToken();

        $id = $this->withToken($token)->postJson('/api/v1/admin/tours', [
            'slug' => 'demo', 'name' => 'Demo', 'artist_name' => 'X',
        ])->assertStatus(201)->json('id');

        $this->withToken($token)->putJson("/api/v1/admin/tours/{$id}", ['name' => 'Demo 2'])
            ->assertOk()->assertJsonPath('name', 'Demo 2');

        $this->withToken($token)->deleteJson("/api/v1/admin/tours/{$id}")->assertOk();
        $this->assertSoftDeleted('tours', ['id' => $id]);

        // No aparece sin with_trashed; sí con él.
        $this->withToken($token)->getJson('/api/v1/admin/tours')->assertJsonCount(0, 'data');
        $this->withToken($token)->getJson('/api/v1/admin/tours?with_trashed=1')->assertJsonCount(1, 'data');

        $this->withToken($token)->postJson("/api/v1/admin/tours/{$id}/restore")->assertOk();
        $this->withToken($token)->getJson('/api/v1/admin/tours')->assertJsonCount(1, 'data');
    }

    public function test_manual_order_verification_issues_tickets(): void
    {
        $token = $this->adminToken();
        $tour = Tour::create(['slug' => 't', 'name' => 'T', 'artist_name' => 'A']);
        $event = Event::create(['tour_id' => $tour->id, 'slug' => 'e', 'name' => 'E', 'capacity' => 10]);
        $type = TicketType::create(['tour_id' => $tour->id, 'event_id' => $event->id, 'slug' => 'n', 'name' => 'N', 'price' => 100]);
        $customer = Customer::create(['first_name' => 'A', 'last_name' => 'B', 'document_number' => '1', 'email' => 'a@e.com']);
        $order = Order::create([
            'customer_id' => $customer->id, 'payment_status' => Order::STATUS_PENDING_VERIFICATION,
            'amount' => 200, 'quantity' => 2, 'ticket_type_id' => $type->id, 'event_id' => $event->id,
        ]);

        $this->withToken($token)->postJson("/api/v1/admin/orders/{$order->id}/verify")
            ->assertOk()
            ->assertJsonPath('payment_status', 'verified')
            ->assertJsonCount(2, 'tickets');

        $this->assertDatabaseCount('tickets', 2);

        // Reject sobre una orden ya verificada -> 409.
        $this->withToken($token)->postJson("/api/v1/admin/orders/{$order->id}/reject", ['reason' => 'x'])
            ->assertStatus(409);
    }

    public function test_dashboard_metrics(): void
    {
        $token = $this->adminToken();
        $tour = Tour::create(['slug' => 't', 'name' => 'T', 'artist_name' => 'A']);
        $event = Event::create(['tour_id' => $tour->id, 'slug' => 'e', 'name' => 'E', 'capacity' => 100]);

        $this->withToken($token)->getJson("/api/v1/admin/dashboard/metrics?event_id={$event->id}")
            ->assertOk()
            ->assertJsonPath('overview.capacity', 100)
            ->assertJsonPath('overview.issued', 0);
    }

    public function test_api_client_creation_returns_secret_and_can_rotate(): void
    {
        $token = $this->adminToken();

        $res = $this->withToken($token)->postJson('/api/v1/admin/api-clients', [
            'name' => 'Tienda', 'client_id' => 'shop-1', 'scopes' => ['orders:write', 'tickets:read'],
        ])->assertStatus(201);

        $res->assertJsonStructure(['client' => ['id', 'client_id'], 'client_secret']);
        $id = $res->json('client.id');

        $rotated = $this->withToken($token)->postJson("/api/v1/admin/api-clients/{$id}/rotate-secret")
            ->assertOk()->json();

        $this->assertNotEmpty($rotated['client_secret']);
        $this->assertSame('shop-1', $rotated['client_id']);
    }
}
