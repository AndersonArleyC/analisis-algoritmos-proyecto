<?php

namespace App\Services\Algorithms;

class MergeSort
{
    /**
     * Ordena sin modificar la entrada; el comparador debe ser consistente y puro.
     * Tiempo O(n log n) y espacio auxiliar O(n), sin almacenar la traza.
     * Registrar instantáneas de los pasos consume memoria adicional.
     *
     * @template T
     *
     * @param  list<T>  $items
     * @param  callable(T, T): int  $compare
     * @return array{items: list<T>, comparisons: int, trace: list<array<string, mixed>>}
     */
    public function sort(array $items, callable $compare, bool $recordTrace = false): array
    {
        $comparisons = 0;
        $trace = $recordTrace ? [] : null;
        // Ordenar posiciones permite distinguir duplicados sin alterar los valores.
        $positions = array_keys($items);
        $comparePositions = fn (int $left, int $right): int => $compare($items[$left], $items[$right]);

        if ($trace !== null) {
            $this->record($trace, 'input', 0, 0, count($items), 0, ['positions' => $positions]);
        }

        $sortedPositions = $this->divide($positions, $comparePositions, $comparisons, $trace);

        if ($trace !== null) {
            $this->record($trace, 'result', 0, 0, count($items), $comparisons, ['positions' => $sortedPositions]);
        }

        $sorted = [];

        foreach ($sortedPositions as $position) {
            $sorted[] = $items[$position];
        }

        return ['items' => $sorted, 'comparisons' => $comparisons, 'trace' => $trace ?? []];
    }

    /**
     * @param  list<int>  $positions
     * @param  callable(int, int): int  $compare
     * @param  list<array<string, mixed>>|null  $trace
     * @return list<int>
     */
    private function divide(
        array $positions,
        callable $compare,
        int &$comparisons,
        ?array &$trace,
        int $start = 0,
        int $depth = 0,
    ): array {
        $count = count($positions);

        if ($count <= 1) {
            return $positions;
        }

        $middle = intdiv($count, 2);
        $left = array_slice($positions, 0, $middle);
        $right = array_slice($positions, $middle);

        if ($trace !== null) {
            $this->record($trace, 'split', $depth, $start, $start + $count, $comparisons, [
                'positions' => $positions, 'left' => $left, 'right' => $right,
            ]);
        }

        $left = $this->divide($left, $compare, $comparisons, $trace, $start, $depth + 1);
        $right = $this->divide($right, $compare, $comparisons, $trace, $start + $middle, $depth + 1);

        return $this->merge($left, $right, $compare, $comparisons, $trace, $start, $depth);
    }

    /**
     * @param  list<int>  $left
     * @param  list<int>  $right
     * @param  callable(int, int): int  $compare
     * @param  list<array<string, mixed>>|null  $trace
     * @return list<int>
     */
    private function merge(
        array $left,
        array $right,
        callable $compare,
        int &$comparisons,
        ?array &$trace,
        int $start,
        int $depth,
    ): array {
        $merged = [];
        $leftIndex = 0;
        $rightIndex = 0;
        $leftCount = count($left);
        $rightCount = count($right);

        while ($leftIndex < $leftCount && $rightIndex < $rightCount) {
            $leftPosition = $left[$leftIndex];
            $rightPosition = $right[$rightIndex];
            $outcome = $compare($leftPosition, $rightPosition);
            $comparisons++;

            // Tomar primero la izquierda en empates conserva el orden de entrada.
            if ($outcome <= 0) {
                $merged[] = $left[$leftIndex++];
            } else {
                $merged[] = $right[$rightIndex++];
            }

            if ($trace !== null) {
                $this->record($trace, 'compare', $depth, $start, $start + $leftCount + $rightCount, $comparisons, [
                    'left' => $leftPosition,
                    'right' => $rightPosition,
                    'outcome' => $outcome <=> 0,
                    'chosen' => $outcome <= 0 ? $leftPosition : $rightPosition,
                    'merged' => $merged,
                ]);
            }
        }

        // Copiar sobrantes no requiere comparar claves ni desplazar la lista.
        while ($leftIndex < $leftCount) {
            $merged[] = $left[$leftIndex++];
        }

        while ($rightIndex < $rightCount) {
            $merged[] = $right[$rightIndex++];
        }

        if ($trace !== null) {
            $this->record($trace, 'merge', $depth, $start, $start + $leftCount + $rightCount, $comparisons, [
                'positions' => $merged,
            ]);
        }

        return $merged;
    }

    /**
     * Los arrays se guardan por valor para conservar las instantáneas anteriores.
     *
     * @param  list<array<string, mixed>>  $trace
     * @param  array<string, mixed>  $data
     */
    private function record(array &$trace, string $type, int $depth, int $start, int $end, int $comparisons, array $data): void
    {
        $trace[] = [
            'step' => count($trace),
            'type' => $type,
            'depth' => $depth,
            'range' => [$start, $end],
            'comparisons' => $comparisons,
            'data' => $data,
        ];
    }
}
