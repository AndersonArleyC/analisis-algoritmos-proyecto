<?php

namespace Database\Seeders;

use App\Models\Flight;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FlightSeeder extends Seeder
{
    public function run(): void
    {
        // Vuelos y precios de demostración. Fechas fijas para repetir la búsqueda.
        $searches = [
            ['BOG', 'MDE', '2026-10-15', 'DEMO'],
            ['BOG', 'MDE', '2026-10-16', 'DM2'],
            ['MDE', 'CTG', '2026-10-15', 'DMC'],
            ['CLO', 'BOG', '2026-10-17', 'DCB'],
        ];

        // Código, hora local, duración total, precio COP con impuestos, escalas.
        // Las opciones adicionales conservan los extremos de A/B/C.
        $options = [
            ['A', '08:00', 720, 200000, 2],
            ['B', '08:00', 120, 500000, 0],
            ['C', '08:00', 180, 280000, 1],
            ['D', '10:30', 360, 350000, 1],
            ['E', '12:00', 480, 240000, 2],
            ['F', '14:15', 240, 400000, 1],
            ['G', '18:00', 600, 200000, 2],
            ['H', '23:30', 120, 450000, 0],
        ];

        $airlines = ['Alas Demo', 'Cielo Simulado', 'Ruta Académica'];
        $baggage = [
            'Un artículo personal',
            'Un artículo personal y equipaje de cabina de 10 kg',
            'Un artículo personal y equipaje de bodega de 23 kg',
        ];

        DB::transaction(function () use ($searches, $options, $airlines, $baggage): void {
            foreach ($searches as [$origin, $destination, $date, $prefix]) {
                foreach ($options as $index => [$suffix, $time, $duration, $price, $stops]) {
                    $departure = CarbonImmutable::createFromFormat('!Y-m-d H:i', "$date $time", 'America/Bogota');

                    Flight::updateOrCreate([
                        'flight_code' => "$prefix-$suffix",
                        'departure_at' => $departure->format('Y-m-d H:i:s'),
                    ], [
                        'airline' => $airlines[$index % count($airlines)],
                        'origin' => $origin,
                        'destination' => $destination,
                        'arrival_at' => $departure->addMinutes($duration),
                        'duration_minutes' => $duration,
                        'stops' => $stops,
                        'baggage_description' => $baggage[$index % count($baggage)],
                        'total_price_cop' => $price,
                    ]);
                }
            }
        });
    }
}
