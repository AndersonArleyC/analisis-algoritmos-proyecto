<?php

namespace Tests\Unit;

use App\Services\Algorithms\MergeSort;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MergeSortTest extends TestCase
{
    #[DataProvider('numberLists')]
    public function test_sorts_numbers_without_changing_the_input(array $items, array $expected, int $comparisons): void
    {
        $original = $items;
        $calls = 0;
        $compare = function ($left, $right) use (&$calls): int {
            $calls++;

            return $left <=> $right;
        };

        $result = (new MergeSort)->sort($items, $compare);

        $this->assertSame([
            'items' => $expected,
            'comparisons' => $comparisons,
            'trace' => [],
        ], $result);
        $this->assertSame($calls, $result['comparisons']);
        $this->assertSame($original, $items);
    }

    public static function numberLists(): array
    {
        return [
            'empty' => [[], [], 0],
            'single item' => [[7], [7], 0],
            'two items' => [[200000, 100000], [100000, 200000], 1],
            'already sorted' => [[1, 2, 3, 4], [1, 2, 3, 4], 4],
            'reverse order' => [[4, 3, 2, 1], [1, 2, 3, 4], 4],
            'duplicates' => [[3, 1, 3, 2, 1], [1, 1, 2, 3, 3], 8],
            'all equal' => [[2, 2, 2, 2], [2, 2, 2, 2], 4],
            'negative and decimal values' => [[0.2, -1, 0.1], [-1, 0.1, 0.2], 3],
        ];
    }

    public function test_preserves_input_order_for_equal_keys_across_both_halves(): void
    {
        $items = [
            ['label' => 'A', 'key' => 2],
            ['label' => 'B', 'key' => 1],
            ['label' => 'C', 'key' => 2],
            ['label' => 'D', 'key' => 1],
            ['label' => 'E', 'key' => 2],
            ['label' => 'F', 'key' => 1],
        ];
        $original = $items;

        $result = (new MergeSort)->sort(
            $items,
            fn (array $left, array $right): int => $left['key'] <=> $right['key'],
        );

        $this->assertSame([
            $items[1], $items[3], $items[5], $items[0], $items[2], $items[4],
        ], $result['items']);
        $this->assertSame($original, $items);
    }

    public function test_uses_the_comparator_sign_for_custom_ordering(): void
    {
        $result = (new MergeSort)->sort(
            ['a', 'ccc', 'bb', 'ddd'],
            fn (string $left, string $right): int => 10 * (strlen($right) <=> strlen($left)),
        );

        $this->assertSame(['ccc', 'ddd', 'bb', 'a'], $result['items']);
    }

    public function test_splits_odd_lists_and_processes_the_left_half_first(): void
    {
        $pairs = [];
        $result = (new MergeSort)->sort([5, 4, 3, 2, 1], function ($left, $right) use (&$pairs): int {
            $pairs[] = [$left, $right];

            return $left <=> $right;
        });

        $this->assertSame([[5, 4], [2, 1], [3, 1], [3, 2], [4, 1], [4, 2], [4, 3]], $pairs);
        $this->assertSame(7, $result['comparisons']);
        $this->assertSame([1, 2, 3, 4, 5], $result['items']);
    }

    public function test_comparison_count_restarts_on_each_call(): void
    {
        $sorter = new MergeSort;
        $compare = fn ($left, $right): int => $left <=> $right;

        $this->assertSame(3, $sorter->sort([3, 1, 2], $compare)['comparisons']);
        $this->assertSame(0, $sorter->sort([], $compare)['comparisons']);
        $this->assertSame(1, $sorter->sort([2, 1], $compare)['comparisons']);
    }
}
