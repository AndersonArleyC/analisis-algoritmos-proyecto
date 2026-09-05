<?php

namespace App\Services;

use App\Models\Flight;

class FlightAvailability
{
    /**
     * Resumen compacto: origen -> destino -> fecha local -> cantidad y mínimo.
     * A2 almacena departure_at en hora local de America/Bogota, sin offset en SQLite.
     */
    public function summary(): array
    {
        $days = Flight::query()
            ->select(['origin', 'destination'])
            ->selectRaw('DATE(departure_at) AS day, COUNT(*) AS flight_count, MIN(total_price_cop) AS min_price')
            ->groupBy('origin', 'destination')
            ->groupByRaw('DATE(departure_at)')
            // Orden auxiliar del calendario, nunca del ranking de vuelos.
            ->orderBy('day')->orderBy('origin')->orderBy('destination')->get();

        $summary = [];
        foreach ($days as $day) {
            $summary[$day->origin][$day->destination][$day->day] = [
                'count' => (int) $day->flight_count,
                'min_price' => (int) $day->min_price,
            ];
        }

        return $summary;
    }

    public function example(array $summary): ?array
    {
        // Preferir la búsqueda académica original si sigue presente.
        if (isset($summary['BOG']['MDE']['2026-10-15'])) {
            return ['origin' => 'BOG', 'destination' => 'MDE', 'departure_date' => '2026-10-15'];
        }

        foreach ($summary as $origin => $destinations) {
            foreach ($destinations as $destination => $days) {
                foreach ($days as $date => $day) {
                    return ['origin' => $origin, 'destination' => $destination, 'departure_date' => $date];
                }
            }
        }

        return null;
    }
}
