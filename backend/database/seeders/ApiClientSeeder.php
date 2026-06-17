<?php

namespace Database\Seeders;

use App\Models\ApiClient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Cliente máquina de ejemplo (la tienda WordPress de Javi).
 * Credenciales DEV (cambiar/rotar en producción):
 *   client_id     = wp-store-demo
 *   client_secret = dev-client-secret
 *   webhook_secret = dev-webhook-secret
 */
class ApiClientSeeder extends Seeder
{
    public function run(): void
    {
        ApiClient::updateOrCreate(
            ['client_id' => 'wp-store-demo'],
            [
                'name' => 'Tienda WordPress (demo)',
                'client_secret_hash' => Hash::make('dev-client-secret'),
                'webhook_secret' => 'dev-webhook-secret',
                'scopes' => ['orders:write', 'tickets:read'],
                'is_active' => true,
            ]
        );
    }
}
