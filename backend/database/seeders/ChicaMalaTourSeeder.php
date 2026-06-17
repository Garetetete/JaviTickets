<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\TicketType;
use App\Models\Tour;
use Illuminate\Database\Seeder;

/**
 * Caso real de prueba: CHICA MALA TOUR (artista "Dennis Fernando"),
 * con 1 evento de ejemplo y 3 tipos de ticket (Normal/Premium/Diamante).
 * Precios placeholder editables desde admin.
 */
class ChicaMalaTourSeeder extends Seeder
{
    public function run(): void
    {
        $tour = Tour::updateOrCreate(
            ['slug' => 'chica-mala-tour'],
            [
                'name' => 'CHICA MALA TOUR',
                'artist_name' => 'Dennis Fernando',
                'owner_name' => 'Productora Chica Mala',
                'owner_email' => 'contacto@chicamala.example',
                'is_active' => true,
            ]
        );

        $event = Event::updateOrCreate(
            ['tour_id' => $tour->id, 'slug' => 'bogota'],
            [
                'name' => 'Bogotá',
                'country' => 'Colombia',
                'venue' => 'Movistar Arena',
                'event_date' => now()->addMonths(3)->setTime(20, 0),
                'capacity' => 5000,
                'is_active' => true,
            ]
        );

        $types = [
            ['slug' => 'normal',   'name' => 'Normal',   'price' => 300.00,  'quota' => 4000, 'order' => 1],
            ['slug' => 'premium',  'name' => 'Premium',  'price' => 500.00,  'quota' => 800,  'order' => 2],
            ['slug' => 'diamante', 'name' => 'Diamante', 'price' => 1000.00, 'quota' => 200,  'order' => 3],
        ];

        foreach ($types as $type) {
            TicketType::updateOrCreate(
                ['tour_id' => $tour->id, 'event_id' => $event->id, 'slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'price' => $type['price'],
                    'currency' => 'USD',
                    'quota' => $type['quota'],
                    'order' => $type['order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
