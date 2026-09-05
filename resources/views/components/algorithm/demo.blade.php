@props(['demonstration' => null])

@once
    <link rel="stylesheet" href="{{ asset('algorithm/demo.css') }}">
    <script src="{{ asset('algorithm/demo.js') }}" defer></script>
@endonce

<section {{ $attributes->class(['algorithm-demo']) }} data-algorithm-demo aria-label="Cómo funciona Merge Sort">
    <header class="algorithm-demo__header">
        <p class="algorithm-demo__eyebrow">AeroCompare · Paso a paso</p>
        <h2>Cómo funciona Merge Sort</h2>
        <p>Explora cómo se dividen los vuelos y se combinan en orden, una comparación a la vez.</p>
    </header>

    @if ($demonstration === null)
        <p class="algorithm-demo__notice">No hay una demostración disponible para mostrar.</p>
    @else
        @php
            $input = $demonstration['input'];
            $key = $demonstration['key'];
            $trace = $demonstration['trace'];
            $keyLabel = match ($key) {
                'total_price_cop' => 'Precio total (COP)',
                'duration_minutes' => 'Duración total (minutos)',
                'score' => 'Puntuación de equilibrio',
            };
            $eventLabels = [
                'input' => 'Entrada', 'split' => 'División', 'compare' => 'Comparación',
                'merge' => 'Combinación', 'result' => 'Resultado final',
            ];
        @endphp

        <div class="algorithm-demo__summary">
            <p>Demostración de los primeros {{ count($input) }} vuelos de {{ $demonstration['total_results'] }} resultados, en su orden de entrada.</p>
            <p class="algorithm-demo__muted">Incluye hasta ocho vuelos. El ranking completo procesa todos los resultados.</p>
            <dl class="algorithm-demo__metrics">
                <div><dt>Clave comparada</dt><dd>{{ $keyLabel }}</dd></div>
                <div><dt>Comparaciones totales de esta demostración</dt><dd>{{ $demonstration['comparisons'] }}</dd></div>
            </dl>
            @if ($key === 'score')
                <p class="algorithm-demo__note">Menor puntuación significa mejor equilibrio según los pesos elegidos. Se conserva la normalización de todos los resultados de la búsqueda. Es una regla del proyecto, no un porcentaje de calidad. Aquí se muestran seis decimales; las comparaciones usan los valores sin redondear.</p>
            @endif
        </div>

        <div class="algorithm-demo__input">
            <h3>Vuelos del subconjunto · orden de entrada</h3>
            <p class="algorithm-demo__muted">La posición original identifica cada vuelo durante todo el recorrido y comienza en cero.</p>
            @include('algorithm.partials.sequence', ['positions' => array_keys($input), 'chosen' => null])
        </div>

        @if ($input === [])
            <p class="algorithm-demo__notice">No se encontraron vuelos para demostrar. La entrada y el resultado están vacíos; no se realizan comparaciones.</p>
        @endif

        @if ($trace === [])
            <p class="algorithm-demo__notice">No hay pasos registrados para esta demostración.</p>
        @else
            <div class="algorithm-demo__navigation" data-demo-controls hidden>
                <div class="algorithm-demo__buttons" role="group" aria-label="Navegar por los pasos">
                    <button type="button" data-demo-previous disabled>Anterior</button>
                    <button type="button" data-demo-next class="algorithm-demo__primary">Siguiente</button>
                    <button type="button" data-demo-reset disabled>Reiniciar</button>
                </div>
                <p class="algorithm-demo__status" data-demo-status role="status" aria-live="polite" aria-atomic="true"></p>
            </div>
            <noscript><p class="algorithm-demo__notice">JavaScript está desactivado. Puedes leer todos los pasos registrados a continuación.</p></noscript>

            <div class="algorithm-demo__events">
                @foreach ($trace as $event)
                    <article class="algorithm-demo__event" data-demo-step="{{ $event['step'] }}" data-demo-type="{{ $event['type'] }}" data-demo-label="{{ $eventLabels[$event['type']] }}" data-demo-comparisons="{{ $event['comparisons'] }}">
                        <div class="algorithm-demo__event-header">
                            <h3>{{ $eventLabels[$event['type']] }}</h3>
                            <span class="algorithm-demo__badge">Paso {{ $event['step'] + 1 }} de {{ count($trace) }}</span>
                        </div>
                        <p class="algorithm-demo__metadata">Nivel {{ $event['depth'] }} · Rango original [{{ $event['range'][0] }}, {{ $event['range'][1] }}) · Comparaciones acumuladas: {{ $event['comparisons'] }}</p>
                        @include('algorithm.partials.event')
                    </article>
                @endforeach
            </div>
        @endif
    @endif

    <footer class="algorithm-demo__footer">
        <p><strong>Dividir y combinar.</strong> Merge Sort divide la lista hasta tener cero o un elemento y combina las sublistas ordenadas. En un empate elige primero el elemento izquierdo, conservando el orden de entrada.</p>
        <p>Tiempo: <strong>O(n log n)</strong>. Espacio auxiliar sin traza: <strong>O(n)</strong>. Guardar los pasos consume memoria adicional. Solo comparar dos claves incrementa el contador; copiar los sobrantes no lo aumenta.</p>
        <p>Los pasos proceden de la ejecución real del algoritmo en PHP. El rango [inicio, fin) excluye la posición final. Vuelos y precios de demostración.</p>
    </footer>
</section>
