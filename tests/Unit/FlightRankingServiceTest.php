<?php

namespace Tests\Unit;

use App\Services\Algorithms\MergeSort;
use App\Services\FlightRankingService;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FlightRankingServiceTest extends TestCase
{
    private FlightRankingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FlightRankingService(new MergeSort);
    }

    public function test_defaults_and_return_structure_match_the_contract(): void
    {
        $flights = $this->exampleFlights();
        $expectedFlights = [];

        foreach ([$flights[0], $flights[2], $flights[1]] as $flight) {
            $expectedFlights[] = array_merge($flight, [
                'normalized_price' => null, 'normalized_duration' => null, 'score' => null,
            ]);
        }

        $this->assertSame([
            'criterion' => 'price',
            'priceWeight' => 0.5,
            'timeWeight' => 0.5,
            'flights' => $expectedFlights,
            'comparisons' => 2,
            'normalization' => null,
            'demonstration' => null,
        ], $this->service->rank($flights));
    }

    #[DataProvider('simpleCriteria')]
    public function test_price_and_duration_order_do_not_depend_on_weight(string $criterion, float $weight, array $expectedIds, int $expectedComparisons): void
    {
        $result = $this->service->rank($this->exampleFlights(), $criterion, $weight);

        $this->assertSame($expectedIds, array_column($result['flights'], 'id'));
        $this->assertSame($weight, $result['priceWeight']);
        $this->assertSame(1 - $weight, $result['timeWeight']);
        $this->assertNull($result['normalization']);
        $this->assertNull($result['demonstration']);
        $this->assertSame($expectedComparisons, $result['comparisons']);

        foreach ($result['flights'] as $flight) {
            $this->assertNull($flight['normalized_price']);
            $this->assertNull($flight['normalized_duration']);
            $this->assertNull($flight['score']);
        }
    }

    public static function simpleCriteria(): iterable
    {
        foreach ([0.0, 0.5, 1.0] as $weight) {
            yield "price, weight $weight" => ['price', $weight, [1, 3, 2], 2];
            yield "duration, weight $weight" => ['duration', $weight, [2, 3, 1], 3];
        }
    }

    #[DataProvider('balancedWeights')]
    public function test_abc_equilibrium_with_each_weight(float $weight, array $expectedIds, array $expectedScores, int $expectedComparisons): void
    {
        $result = $this->service->rank($this->exampleFlights(), 'balanced', $weight);

        $this->assertSame($expectedIds, array_column($result['flights'], 'id'));
        $this->assertSame('balanced', $result['criterion']);
        $this->assertSame($weight, $result['priceWeight']);
        $this->assertSame(1 - $weight, $result['timeWeight']);
        $this->assertSame([
            'min_price' => 200000, 'max_price' => 500000,
            'min_duration' => 120, 'max_duration' => 720,
        ], $result['normalization']);
        $this->assertSame($expectedComparisons, $result['comparisons']);
        $this->assertNull($result['demonstration']);

        $byId = array_column($result['flights'], null, 'id');
        $this->assertSame(0.0, $byId[1]['normalized_price']);
        $this->assertSame(1.0, $byId[1]['normalized_duration']);
        $this->assertSame(1.0, $byId[2]['normalized_price']);
        $this->assertSame(0.0, $byId[2]['normalized_duration']);
        $this->assertEqualsWithDelta(4 / 15, $byId[3]['normalized_price'], 1e-15);
        $this->assertSame(0.1, $byId[3]['normalized_duration']);

        foreach ($expectedScores as $id => $score) {
            $this->assertIsFloat($byId[$id]['score']);
            $this->assertEqualsWithDelta($score, $byId[$id]['score'], 1e-15);
        }
    }

    public static function balancedWeights(): array
    {
        return [
            'time only' => [0.0, [2, 3, 1], [1 => 1.0, 2 => 0.0, 3 => 0.1], 3],
            'equal weights' => [0.5, [3, 1, 2], [1 => 0.5, 2 => 0.5, 3 => 11 / 60], 3],
            'price only' => [1.0, [1, 3, 2], [1 => 0.0, 2 => 1.0, 3 => 4 / 15], 2],
        ];
    }

    #[DataProvider('constantComponents')]
    public function test_constant_components_normalize_to_zero(array $prices, array $durations, array $expectedIds, array $expectedComponents): void
    {
        $flights = [
            $this->flight(9, $prices[0], $durations[0]),
            $this->flight(2, $prices[1], $durations[1]),
            $this->flight(7, $prices[2], $durations[2]),
        ];
        $result = $this->service->rank($flights, 'balanced');

        $this->assertSame($expectedIds, array_column($result['flights'], 'id'));

        foreach ($result['flights'] as $flight) {
            $this->assertSame($expectedComponents[$flight['id']], [
                $flight['normalized_price'], $flight['normalized_duration'], $flight['score'],
            ]);
        }
    }

    public static function constantComponents(): array
    {
        return [
            'equal prices' => [[200, 200, 200], [300, 100, 200], [2, 7, 9], [
                9 => [0.0, 1.0, 0.5], 2 => [0.0, 0.0, 0.0], 7 => [0.0, 0.5, 0.25],
            ]],
            'equal durations' => [[300, 100, 200], [120, 120, 120], [2, 7, 9], [
                9 => [1.0, 0.0, 0.5], 2 => [0.0, 0.0, 0.0], 7 => [0.5, 0.0, 0.25],
            ]],
            'both equal' => [[200, 200, 200], [120, 120, 120], [9, 2, 7], [
                9 => [0.0, 0.0, 0.0], 2 => [0.0, 0.0, 0.0], 7 => [0.0, 0.0, 0.0],
            ]],
        ];
    }

    #[DataProvider('criteria')]
    public function test_empty_lists(string $criterion): void
    {
        $this->assertSame([
            'criterion' => $criterion,
            'priceWeight' => 0.5,
            'timeWeight' => 0.5,
            'flights' => [],
            'comparisons' => 0,
            'normalization' => null,
            'demonstration' => null,
        ], $this->service->rank([], $criterion));
    }

    #[DataProvider('criteria')]
    public function test_single_flights(string $criterion): void
    {
        $flight = $this->flight(1, 0, 120);
        $result = $this->service->rank([$flight], $criterion);
        $component = $criterion === 'balanced' ? 0.0 : null;

        $this->assertSame([array_merge($flight, [
            'normalized_price' => $component, 'normalized_duration' => $component, 'score' => $component,
        ])], $result['flights']);
        $this->assertSame(0, $result['comparisons']);
        $this->assertSame($criterion === 'balanced' ? [
            'min_price' => 0, 'max_price' => 0, 'min_duration' => 120, 'max_duration' => 120,
        ] : null, $result['normalization']);
        $this->assertNull($result['demonstration']);
    }

    #[DataProvider('criteria')]
    public function test_original_fields_and_input_are_preserved(string $criterion): void
    {
        $flights = $this->exampleFlights();
        $original = $flights;
        $result = $this->service->rank($flights, $criterion);

        $this->assertSame($original, $flights);

        foreach ($result['flights'] as $flight) {
            unset($flight['normalized_price'], $flight['normalized_duration'], $flight['score']);
            $this->assertSame($original[$flight['id'] - 1], $flight);
        }
    }

    public static function criteria(): array
    {
        return ['price' => ['price'], 'duration' => ['duration'], 'balanced' => ['balanced']];
    }

    #[DataProvider('tiedFlights')]
    public function test_ties_preserve_input_order_without_secondary_keys(string $criterion, array $prices, array $durations): void
    {
        $flights = [];

        foreach ([90, 80, 70, 60, 50, 40] as $index => $id) {
            $flights[] = $this->flight($id, $prices[$index], $durations[$index]);
        }

        $result = $this->service->rank($flights, $criterion);

        $this->assertSame([80, 60, 40, 90, 70, 50], array_column($result['flights'], 'id'));
    }

    public static function tiedFlights(): array
    {
        return [
            'price' => ['price', [200, 100, 200, 100, 200, 100], [60, 60, 50, 50, 40, 40]],
            'duration' => ['duration', [60, 60, 50, 50, 40, 40], [200, 100, 200, 100, 200, 100]],
            'balanced' => ['balanced', [200, 100, 200, 100, 200, 100], [200, 100, 200, 100, 200, 100]],
        ];
    }

    public function test_scores_are_not_rounded_or_treated_as_ties(): void
    {
        $flights = [
            $this->flight(9, 500001, 120),
            $this->flight(2, 500000, 120),
            $this->flight(7, 0, 120),
            $this->flight(4, 1000000, 120),
        ];
        $result = $this->service->rank($flights, 'balanced');
        $byId = array_column($result['flights'], null, 'id');

        $this->assertSame([7, 2, 9, 4], array_column($result['flights'], 'id'));
        $this->assertSame(0.2500005, $byId[9]['score']);
        $this->assertSame(0.25, $byId[2]['score']);
    }

    public function test_normalization_and_ranking_use_all_flights_and_reset_between_calls(): void
    {
        $flights = [];

        for ($id = 1; $id <= 8; $id++) {
            $flights[] = $this->flight($id, 200000, 180);
        }

        $flights[] = $this->flight(9, 100000, 120);
        $flights[] = $this->flight(10, 500000, 720);
        $result = $this->service->rank($flights, 'balanced');

        $this->assertSame([9, 1, 2, 3, 4, 5, 6, 7, 8, 10], array_column($result['flights'], 'id'));
        $this->assertSame([
            'min_price' => 100000, 'max_price' => 500000,
            'min_duration' => 120, 'max_duration' => 720,
        ], $result['normalization']);
        $this->assertSame(0.25, $result['flights'][1]['normalized_price']);
        $this->assertSame(0.1, $result['flights'][1]['normalized_duration']);
        $this->assertSame(0.175, $result['flights'][1]['score']);

        $next = $this->service->rank([], 'balanced');
        $this->assertNull($next['normalization']);
        $this->assertSame(0, $next['comparisons']);
        $this->assertSame([], $next['flights']);
    }

    #[DataProvider('invalidCriteria')]
    public function test_invalid_criteria_are_rejected_even_for_empty_lists(string $criterion, bool $empty): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->rank($empty ? [] : $this->exampleFlights(), $criterion);
    }

    public static function invalidCriteria(): iterable
    {
        foreach (['', 'Price', ' price', 'score'] as $criterion) {
            yield "$criterion, empty" => [$criterion, true];
            yield "$criterion, populated" => [$criterion, false];
        }
    }

    #[DataProvider('invalidWeights')]
    public function test_invalid_weights_are_rejected_for_every_criterion(string $criterion, float $weight, bool $empty): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->rank($empty ? [] : $this->exampleFlights(), $criterion, $weight);
    }

    public static function invalidWeights(): iterable
    {
        foreach (['price', 'duration', 'balanced'] as $criterion) {
            foreach (['negative' => -0.01, 'above one' => 1.01, 'nan' => NAN, 'infinity' => INF, 'negative infinity' => -INF] as $name => $weight) {
                yield "$criterion, $name, empty" => [$criterion, $weight, true];
                yield "$criterion, $name, populated" => [$criterion, $weight, false];
            }
        }
    }

    private function exampleFlights(): array
    {
        return [
            $this->flight(1, 200000, 720),
            $this->flight(2, 500000, 120),
            $this->flight(3, 280000, 180),
        ];
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
