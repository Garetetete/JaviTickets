<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Event;
use Illuminate\Database\Seeder;

/**
 * Usuarios DEV:
 *   admin@qrtickets.test / password   (rol admin)
 *   gate@qrtickets.test  / password   (rol gate, asignado al primer evento)
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        AdminUser::updateOrCreate(
            ['email' => 'admin@qrtickets.test'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'role' => 'admin',
                'event_id' => null,
                'is_active' => true,
            ]
        );

        AdminUser::updateOrCreate(
            ['email' => 'gate@qrtickets.test'],
            [
                'name' => 'Operador Puerta',
                'password' => 'password',
                'role' => 'gate',
                'event_id' => Event::query()->value('id'),
                'is_active' => true,
            ]
        );
    }
}
