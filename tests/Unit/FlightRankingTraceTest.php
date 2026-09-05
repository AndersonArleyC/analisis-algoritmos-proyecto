<?php

namespace Tests\Unit;

use App\Services\Algorithms\MergeSort;
use App\Services\FlightRankingService;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FlightRankingTraceTest extends TestCase
{
    #[DataProvider('demonstrationCases')]
    public function test_only_the_demonstration_is_limited_and_counters_are_independent(
        string $criterion,
        string $key,
        int $size,
        int $expectedComparisons,
        int $expectedDemoComparisons,
    ): void {
        $flights = [];

        for ($index = 0; $index < $size; $index++) {
            $flights[] = $this->flight(100 + $index, ($size - $index) * 10000, ($size - $index) * 30);
        }

        $original = $flights;
        $service = new FlightRankingService(new MergeSort);
        $withoutTrace = $service->rank($flights, $criterion);
        $result = $service->rank($flights, $criterion, 0.5, true);
        $demo = $result['demonstration'];
        $selected = array_slice($original, 0, 8);

        $this->assertSame(['selection', 'limit', 'total_results', 'criterion', 'key', 'input', 'flights', 'comparisons', 'trace'], array_keys($demo));
        $this->assertSame('first_input_items', $demo['selection']);
        $this->assertSame(8, $demo['limit']);
        $this->assertSame($size, $demo['total_results']);
        $this->assertSame($criterion, $demo['criterion']);
        $this->assertSame($key, $demo['key']);
        $this->assertCount($size, $result['flights']);
        $this->assertCount(min(8, $size), $demo['input']);
        $this->assertSame(array_column($selected, 'id'), array_column($demo['input'], 'id'));
        $this->assertSame(array_reverse(array_column($original, 'id')), array_column($result['flights'], 'id'));
        $this->assertSame(array_reverse(array_column($selected, 'id')), array_column($demo['flights'], 'id'));
        $this->assertSame($expectedComparisons, $result['comparisons']);
        $this->assertSame($expectedDemoComparisons, $demo['comparisons']);
        $this->assertSame($original, $flights);

        // Cada vuelo demostrado conserva exactamente sus campos y puntuación del ranking global.
        $globalById = array_column($result['flights'], null, 'id');

        foreach ($demo['input'] as $index => $flight) {
            $this->assertSame($globalById[$flight['id']], $flight);
            unset($flight['normalized_price'], $flight['normalized_duration'], $flight['score']);
            $this->assertSame($selected[$index], $flight);
        }

        $this->assertFinalEventMatchesDemonstration($demo);
        $result['demonstration'] = null;
        $this->assertSame($withoutTrace, $result);
        $this->assertSame($withoutTrace, $service->rank($flights, $criterion));

        if ($size <= 1) {
            $this->assertSame(['input', 'result'], array_column($demo['trace'], 'type'));
        }
    }

    public static function demonstrationCases(): iterable
    {
        foreach (['price' => 'total_price_cop', 'duration' => 'duration_minutes', 'balanced' => 'score'] as $criterion => $key) {
            foreach ([0 => 0, 1 => 0, 7 => 11, 8 => 12, 9 => 16, 10 => 19] as $size => $comparisons) {
                yield "$criterion, $size flights" => [$criterion, $key, $size, $comparisons, $size > 8 ? 12 : $comparisons];
            }
        }
    }

    public function test_balanced_demonstration_uses_extremes_outside_the_first_eight(): void
    {
        $flights = [$this->flight(1, 200000, 720), $this->flight(2, 500000, 120)];

        for ($id = 3; $id <= 8; $id++) {
            $flights[] = $this->flight($id, 280000, 180);
        }

        // Este extremo externo hace que B preceda a C con la normalización global.
        $flights[] = $this->flight(9, 3200000, 720);
        $result = (new FlightRankingService(new MergeSort))->rank($flights, 'balanced', 0.5, true);
        $demo = $result['demonstration'];

        $this->assertSame([
            'min_price' => 200000, 'max_price' => 3200000,
            'min_duration' => 120, 'max_duration' => 720,
        ], $result['normalization']);
        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8], array_column($demo['input'], 'id'));
        $this->assertSame([2, 3, 4, 5, 6, 7, 8, 1], array_column($demo['flights'], 'id'));
        $this->assertSame([2, 3, 4, 5, 6, 7, 8, 1, 9], array_column($result['flights'], 'id'));
        $this->assertSame(0.1, $demo['input'][1]['normalized_price']);
        $this->assertSame(0.0, $demo['input'][1]['normalized_duration']);
        $this->assertSame(0.05, $demo['input'][1]['score']);
        $this->assertEqualsWithDelta(2 / 75, $demo['input'][2]['normalized_price'], 1e-15);
        $this->assertSame(0.1, $demo['input'][2]['normalized_duration']);
        $this->assertEqualsWithDelta(19 / 300, $demo['input'][2]['score'], 1e-15);
        $this->assertFinalEventMatchesDemonstration($demo);
    }

    #[DataProvider('criteria')]
    public function test_equal_flights_remain_stable_in_the_demonstration(string $criterion): void
    {
        $flights = [];

        foreach ([40, 10, 70, 20, 60, 30, 90, 50, 80] as $id) {
            $flights[] = $this->flight($id, 200000, 120);
        }

        $result = (new FlightRankingService(new MergeSort))->rank($flights, $criterion, 0.5, true);
        $demo = $result['demonstration'];

        $this->assertSame([40, 10, 70, 20, 60, 30, 90, 50, 80], array_column($result['flights'], 'id'));
        $this->assertSame([40, 10, 70, 20, 60, 30, 90, 50], array_column($demo['flights'], 'id'));

        foreach ($demo['trace'] as $event) {
            if ($event['type'] === 'compare') {
                $this->assertSame(0, $event['data']['outcome']);
                $this->assertSame($event['data']['left'], $event['data']['chosen']);
            }
        }

        $this->assertFinalEventMatchesDemonstration($demo);
    }

    public static function criteria(): array
    {
        return ['price' => ['price'], 'duration' => ['duration'], 'balanced' => ['balanced']];
    }

    private function assertFinalEventMatchesDemonstration(array $demo): void
    {
        $trace = $demo['trace'];
        $first = $trace[0];
        $last = $trace[array_key_last($trace)];

        $this->assertSame([
            'step' => 0, 'type' => 'input', 'depth' => 0, 'range' => [0, count($demo['input'])],
            'comparisons' => 0, 'data' => ['positions' => array_keys($demo['input'])],
        ], $first);
        $this->assertSame('result', $last['type']);
        $this->assertSame(0, $last['depth']);
        $this->assertSame([0, count($demo['input'])], $last['range']);
        $this->assertSame(count($trace) - 1, $last['step']);
        $this->assertSame($demo['comparisons'], $last['comparisons']);
        $compareEvents = array_filter($trace, fn (array $event): bool => $event['type'] === 'compare');
        $this->assertCount($demo['comparisons'], $compareEvents);
        $replayed = array_map(fn (int $position): array => $demo['input'][$position], $last['data']['positions']);
        $this->assertSame($demo['flights'], $replayed);
    }

    private function flight(int $id, int $price, int $duration): array
    {
        $departure = new DateTimeImmutable('2026-10-15T08:00:00-05:00');

        return [
            'id' => $id,
            'airline' => 'Aerolínea de demostración',
            'flight_code' => 'DEMO-'.$id,
            'origin' => 'BOG',
            'destination' => 'MDE',
            'departure_at' => $departure->format(DATE_ATOM),
            'arrival_at' => $departure->modify("+$duration minutes")->format(DATE_ATOM),
            'duration_minutes' => $duration,
            'stops' => 0,
            'baggage_description' => 'Un artículo personal',
            'total_price_cop' => $price,
        ];
    }
}
