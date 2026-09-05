<?php

namespace App\Services;

use App\Services\Algorithms\MergeSort;
use InvalidArgumentException;

class FlightRankingService
{
    public function __construct(private readonly MergeSort $mergeSort) {}

    /**
     * Recibe FlightData válidos según docs/contrato-integracion.md, sin modificarlos.
     * La demostración usa hasta ocho vuelos y reutiliza su normalización global.
     *
     * @param  list<array<string, mixed>>  $flights
     * @return array{
     *     criterion: string,
     *     priceWeight: float,
     *     timeWeight: float,
     *     flights: list<array<string, mixed>>,
     *     comparisons: int,
     *     normalization: array{min_price: int, max_price: int, min_duration: int, max_duration: int}|null,
     *     demonstration: array{
     *         selection: string, limit: int, total_results: int, criterion: string, key: string,
     *         input: list<array<string, mixed>>, flights: list<array<string, mixed>>,
     *         comparisons: int, trace: list<array<string, mixed>>
     *     }|null
     * }
     */
    public function rank(
        array $flights,
        string $criterion = 'price',
        float $priceWeight = 0.5,
        bool $includeTrace = false,
    ): array {
        // Validar las opciones incluso si la lista está vacía o no usa puntuación.
        if (! in_array($criterion, ['price', 'duration', 'balanced'], true)) {
            throw new InvalidArgumentException('El criterio debe ser price, duration o balanced.');
        }

        if (! is_finite($priceWeight) || $priceWeight < 0 || $priceWeight > 1) {
            throw new InvalidArgumentException('El peso del precio debe ser un número finito entre 0 y 1.');
        }

        $timeWeight = 1 - $priceWeight;
        $normalization = $criterion === 'balanced' && $flights !== []
            ? $this->findNormalization($flights)
            : null;
        $rankedFlights = [];

        foreach ($flights as $flight) {
            $flight['normalized_price'] = null;
            $flight['normalized_duration'] = null;
            $flight['score'] = null;

            if ($normalization !== null) {
                $flight['normalized_price'] = $this->normalize(
                    $flight['total_price_cop'], $normalization['min_price'], $normalization['max_price'],
                );
                $flight['normalized_duration'] = $this->normalize(
                    $flight['duration_minutes'], $normalization['min_duration'], $normalization['max_duration'],
                );
                $flight['score'] = ($priceWeight * $flight['normalized_price'])
                    + ($timeWeight * $flight['normalized_duration']);
            }

            $rankedFlights[] = $flight;
        }

        $key = match ($criterion) {
            'price' => 'total_price_cop',
            'duration' => 'duration_minutes',
            'balanced' => 'score',
        };

        // Comparar sin redondear ni añadir desempates: Merge Sort conserva la estabilidad.
        $compare = fn (array $left, array $right): int => $left[$key] <=> $right[$key];
        $sorted = $this->mergeSort->sort($rankedFlights, $compare, false);
        $demonstration = null;

        if ($includeTrace) {
            // La entrada aún conserva el orden original y las puntuaciones de todos los vuelos.
            $input = array_slice($rankedFlights, 0, 8);
            $demonstrated = $this->mergeSort->sort($input, $compare, true);
            $demonstration = [
                'selection' => 'first_input_items',
                'limit' => 8,
                'total_results' => count($flights),
                'criterion' => $criterion,
                'key' => $key,
                'input' => $input,
                'flights' => $demonstrated['items'],
                'comparisons' => $demonstrated['comparisons'],
                'trace' => $demonstrated['trace'],
            ];
        }

        return [
            'criterion' => $criterion,
            'priceWeight' => $priceWeight,
            'timeWeight' => $timeWeight,
            'flights' => $sorted['items'],
            'comparisons' => $sorted['comparisons'],
            'normalization' => $normalization,
            'demonstration' => $demonstration,
        ];
    }

    /**
     * Recorre todos los vuelos antes de calcular puntuaciones y ordenar.
     *
     * @param  non-empty-list<array<string, mixed>>  $flights
     * @return array{min_price: int, max_price: int, min_duration: int, max_duration: int}
     */
    private function findNormalization(array $flights): array
    {
        $minPrice = $maxPrice = $flights[0]['total_price_cop'];
        $minDuration = $maxDuration = $flights[0]['duration_minutes'];

        foreach ($flights as $flight) {
            $minPrice = min($minPrice, $flight['total_price_cop']);
            $maxPrice = max($maxPrice, $flight['total_price_cop']);
            $minDuration = min($minDuration, $flight['duration_minutes']);
            $maxDuration = max($maxDuration, $flight['duration_minutes']);
        }

        return [
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'min_duration' => $minDuration,
            'max_duration' => $maxDuration,
        ];
    }

    private function normalize(int $value, int $minimum, int $maximum): float
    {
        return $maximum === $minimum ? 0.0 : ($value - $minimum) / ($maximum - $minimum);
    }
}
