<?php

namespace Tests\Feature\Api;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private function admin(bool $active = true): AdminUser
    {
        return AdminUser::create([
            'name' => 'Admin', 'email' => 'admin@e.com', 'password' => 'password',
            'role' => 'admin', 'is_active' => $active,
        ]);
    }

    public function test_login_returns_token_and_user(): void
    {
        $this->admin();

        $this->postJson('/api/v1/admin/login', ['email' => 'admin@e.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'user' => ['id', 'role']])
            ->assertJsonPath('user.role', 'admin');
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        $this->admin();

        $this->postJson('/api/v1/admin/login', ['email' => 'admin@e.com', 'password' => 'nope'])
            ->assertStatus(401);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->admin(active: false);

        $this->postJson('/api/v1/admin/login', ['email' => 'admin@e.com', 'password' => 'password'])
            ->assertStatus(401);
    }

    public function test_me_requires_token(): void
    {
        $this->getJson('/api/v1/admin/me')->assertStatus(401);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $this->admin();
        $token = $this->postJson('/api/v1/admin/login', ['email' => 'admin@e.com', 'password' => 'password'])
            ->json('access_token');

        $this->withToken($token)->getJson('/api/v1/admin/me')
            ->assertOk()
            ->assertJsonPath('email', 'admin@e.com');
    }
}
