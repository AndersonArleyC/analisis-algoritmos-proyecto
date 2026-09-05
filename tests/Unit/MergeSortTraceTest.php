<?php

namespace Tests\Unit;

use App\Services\Algorithms\MergeSort;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

class MergeSortTraceTest extends TestCase
{
    public function test_trace_matches_the_contract_example_exactly(): void
    {
        $result = (new MergeSort)->sort([200000, 100000], fn ($a, $b): int => $a <=> $b, true);

        $this->assertSame([
            'items' => [100000, 200000],
            'comparisons' => 1,
            'trace' => [
                ['step' => 0, 'type' => 'input', 'depth' => 0, 'range' => [0, 2], 'comparisons' => 0,
                    'data' => ['positions' => [0, 1]]],
                ['step' => 1, 'type' => 'split', 'depth' => 0, 'range' => [0, 2], 'comparisons' => 0,
                    'data' => ['positions' => [0, 1], 'left' => [0], 'right' => [1]]],
                ['step' => 2, 'type' => 'compare', 'depth' => 0, 'range' => [0, 2], 'comparisons' => 1,
                    'data' => ['left' => 0, 'right' => 1, 'outcome' => 1, 'chosen' => 1, 'merged' => [1]]],
                ['step' => 3, 'type' => 'merge', 'depth' => 0, 'range' => [0, 2], 'comparisons' => 1,
                    'data' => ['positions' => [1, 0]]],
                ['step' => 4, 'type' => 'result', 'depth' => 0, 'range' => [0, 2], 'comparisons' => 1,
                    'data' => ['positions' => [1, 0]]],
            ],
        ], $result);
    }

    #[DataProviderExternal(MergeSortTest::class, 'numberLists')]
    public function test_events_replay_real_comparisons_and_merges(array $items, array $expected, int $comparisons): void
    {
        $original = $items;
        $calls = [];
        $sorter = new MergeSort;
        $result = $sorter->sort($items, function ($left, $right) use (&$calls): int {
            $outcome = 37 * ($left <=> $right);
            $calls[] = [$left, $right, $outcome];

            return $outcome;
        }, true);

        $this->assertSame($expected, $result['items']);
        $this->assertSame($comparisons, $result['comparisons']);
        $this->assertSame($original, $items);
        $this->assertCount($comparisons, $calls);
        $this->assertCoherentTrace($items, $result, $calls);

        $withoutTrace = $sorter->sort($items, fn ($a, $b): int => $a <=> $b);
        $this->assertSame(['items' => $expected, 'comparisons' => $comparisons, 'trace' => []], $withoutTrace);

        if (count($items) <= 1) {
            $this->assertSame(['input', 'result'], array_column($result['trace'], 'type'));
        }
    }

    public function test_odd_splits_follow_the_original_ranges_left_first(): void
    {
        $result = (new MergeSort)->sort([5, 4, 3, 2, 1], fn ($a, $b): int => $a <=> $b, true);
        $splits = array_values(array_filter($result['trace'], fn (array $event): bool => $event['type'] === 'split'));

        $this->assertSame([[0, 5], [0, 2], [2, 5], [3, 5]], array_column($splits, 'range'));
        $this->assertSame([0, 1, 1, 2], array_column($splits, 'depth'));
        $this->assertSame(['positions' => [0, 1, 2, 3, 4], 'left' => [0, 1], 'right' => [2, 3, 4]], $splits[0]['data']);
    }

    public function test_ties_use_original_positions_and_snapshots_survive_later_calls(): void
    {
        $items = [
            ['label' => 'A', 'key' => 2], ['label' => 'B', 'key' => 1],
            ['label' => 'C', 'key' => 2], ['label' => 'D', 'key' => 1],
            ['label' => 'E', 'key' => 2], ['label' => 'F', 'key' => 1],
        ];
        $original = $items;
        $calls = [];
        $sorter = new MergeSort;
        $result = $sorter->sort($items, function ($left, $right) use (&$calls): int {
            $outcome = $left['key'] <=> $right['key'];
            $calls[] = [$left, $right, $outcome];

            return $outcome;
        }, true);
        $snapshot = $result;

        $this->assertCoherentTrace($items, $result, $calls);
        $this->assertSame([1, 3, 5, 0, 2, 4], $result['trace'][array_key_last($result['trace'])]['data']['positions']);
        $this->assertSame(['B', 'D', 'F', 'A', 'C', 'E'], array_column($result['items'], 'label'));
        $this->assertSame($original, $items);

        $next = $sorter->sort([2, 1], fn ($a, $b): int => $a <=> $b, true);
        $this->assertSame(0, $next['trace'][0]['step']);
        $this->assertSame(0, $next['trace'][0]['comparisons']);
        $this->assertSame(1, $next['comparisons']);
        $this->assertSame($snapshot, $result);
    }

    /**
     * Reproduce los eventos como lo haría un consumidor, sin volver a ordenar.
     * Comprueba cabeceras, prefijos, sobrantes y el contador contra llamadas reales.
     */
    private function assertCoherentTrace(array $items, array $result, array $calls): void
    {
        $trace = $result['trace'];
        $this->assertSame([
            'step' => 0, 'type' => 'input', 'depth' => 0, 'range' => [0, count($items)],
            'comparisons' => 0, 'data' => ['positions' => array_keys($items)],
        ], $trace[0]);
        $lists = [];

        foreach (array_keys($items) as $position) {
            $lists[$position.':'.($position + 1)] = [$position];
        }

        $stack = [];
        $heads = [];
        $prefixes = [];
        $count = 0;

        foreach (array_slice($trace, 1, -1) as $index => $event) {
            $this->assertSame(['step', 'type', 'depth', 'range', 'comparisons', 'data'], array_keys($event));
            $this->assertSame($index + 1, $event['step']);
            [$start, $end] = $event['range'];
            $this->assertGreaterThanOrEqual(0, $start);
            $this->assertLessThanOrEqual(count($items), $end);
            $this->assertGreaterThanOrEqual(2, $end - $start);
            $middle = $start + intdiv($end - $start, 2);
            $key = "$start:$end";
            $data = $event['data'];

            if ($event['type'] === 'split') {
                $this->assertSame(count($stack), $event['depth']);
                $this->assertSame([
                    'positions' => range($start, $end - 1),
                    'left' => range($start, $middle - 1),
                    'right' => range($middle, $end - 1),
                ], $data);
                $stack[] = $event['range'];
                $heads[$key] = [0, 0];
                $prefixes[$key] = [];
            } else {
                $this->assertSame($event['range'], $stack[array_key_last($stack)]);
                $this->assertSame(count($stack) - 1, $event['depth']);
                $left = $lists["$start:$middle"];
                $right = $lists["$middle:$end"];
                [$leftIndex, $rightIndex] = $heads[$key];

                if ($event['type'] === 'compare') {
                    $this->assertSame(['left', 'right', 'outcome', 'chosen', 'merged'], array_keys($data));
                    $this->assertSame($left[$leftIndex], $data['left']);
                    $this->assertSame($right[$rightIndex], $data['right']);
                    [$actualLeft, $actualRight, $outcome] = $calls[$count];
                    $this->assertSame([$actualLeft, $actualRight], [$items[$data['left']], $items[$data['right']]]);
                    $this->assertSame($outcome <=> 0, $data['outcome']);
                    $side = $outcome <= 0 ? 0 : 1;
                    $chosen = $side === 0 ? $left[$leftIndex] : $right[$rightIndex];
                    $heads[$key][$side]++;
                    $prefixes[$key][] = $chosen;
                    $this->assertSame($chosen, $data['chosen']);
                    $this->assertSame($prefixes[$key], $data['merged']);
                    $count++;
                } else {
                    $this->assertSame('merge', $event['type']);
                    $this->assertTrue($leftIndex === count($left) || $rightIndex === count($right));
                    $positions = array_merge($prefixes[$key], array_slice($left, $leftIndex), array_slice($right, $rightIndex));
                    $this->assertSame(['positions' => $positions], $data);
                    $lists[$key] = $positions;
                    array_pop($stack);
                }
            }

            $this->assertSame($count, $event['comparisons']);
        }

        $this->assertSame([], $stack);
        $positions = $lists['0:'.count($items)] ?? [];
        $this->assertSame([
            'step' => count($trace) - 1, 'type' => 'result', 'depth' => 0, 'range' => [0, count($items)],
            'comparisons' => $count, 'data' => ['positions' => $positions],
        ], $trace[array_key_last($trace)]);
        $this->assertSame($result['comparisons'], $count);
        $this->assertSame($result['items'], array_map(fn (int $position) => $items[$position], $positions));
    }
}
