<?php

namespace App\Services\Algorithms;

class MergeSort
{
    /**
     * Ordena sin modificar la entrada; el comparador debe ser consistente y puro.
     * $recordTrace queda reservado para B3: en B1 la traza siempre está vacía.
     * Tiempo O(n log n) y espacio auxiliar O(n), sin almacenar la traza.
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
        $sorted = $this->divide($items, $compare, $comparisons);

        return ['items' => $sorted, 'comparisons' => $comparisons, 'trace' => []];
    }

    /**
     * @template T
     *
     * @param  list<T>  $items
     * @param  callable(T, T): int  $compare
     * @return list<T>
     */
    private function divide(array $items, callable $compare, int &$comparisons): array
    {
        $count = count($items);

        if ($count <= 1) {
            return $items;
        }

        $middle = intdiv($count, 2);
        $left = $this->divide(array_slice($items, 0, $middle), $compare, $comparisons);
        $right = $this->divide(array_slice($items, $middle), $compare, $comparisons);

        return $this->merge($left, $right, $compare, $comparisons);
    }

    /**
     * @template T
     *
     * @param  list<T>  $left
     * @param  list<T>  $right
     * @param  callable(T, T): int  $compare
     * @return list<T>
     */
    private function merge(array $left, array $right, callable $compare, int &$comparisons): array
    {
        $merged = [];
        $leftIndex = 0;
        $rightIndex = 0;
        $leftCount = count($left);
        $rightCount = count($right);

        while ($leftIndex < $leftCount && $rightIndex < $rightCount) {
            $outcome = $compare($left[$leftIndex], $right[$rightIndex]);
            $comparisons++;

            // Tomar primero la izquierda en empates conserva el orden de entrada.
            if ($outcome <= 0) {
                $merged[] = $left[$leftIndex++];
            } else {
                $merged[] = $right[$rightIndex++];
            }
        }

        // Copiar sobrantes no requiere comparar claves ni desplazar la lista.
        while ($leftIndex < $leftCount) {
            $merged[] = $left[$leftIndex++];
        }

        while ($rightIndex < $rightCount) {
            $merged[] = $right[$rightIndex++];
        }

        return $merged;
    }
}
