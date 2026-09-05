@switch($event['type'])
    @case('input')
        <p>Esta es la lista antes de dividirla. Cada vuelo conserva su posición original.</p>
        @include('algorithm.partials.sequence', ['positions' => $event['data']['positions'], 'chosen' => null])
        @break
    @case('split')
        <p>La lista de este rango se divide en dos sublistas. Primero se procesa la izquierda.</p>
        <h4>Lista que se divide</h4>
        @include('algorithm.partials.sequence', ['positions' => $event['data']['positions'], 'chosen' => null])
        <div class="algorithm-demo__halves">
            <div>
                <h4>Sublista izquierda</h4>
                @include('algorithm.partials.sequence', ['positions' => $event['data']['left'], 'chosen' => null])
            </div>
            <div>
                <h4>Sublista derecha</h4>
                @include('algorithm.partials.sequence', ['positions' => $event['data']['right'], 'chosen' => null])
            </div>
        </div>
        @break
    @case('compare')
        <div class="algorithm-demo__halves">
            <div>
                <h4>Cabecera izquierda</h4>
                @include('algorithm.partials.sequence', ['positions' => [$event['data']['left']], 'chosen' => $event['data']['chosen']])
            </div>
            <div>
                <h4>Cabecera derecha</h4>
                @include('algorithm.partials.sequence', ['positions' => [$event['data']['right']], 'chosen' => $event['data']['chosen']])
            </div>
        </div>
        <p class="algorithm-demo__decision">
            @if ($event['data']['outcome'] === 0)
                Las claves empatan. Se elige la izquierda para conservar el orden de entrada.
            @elseif ($event['data']['outcome'] < 0)
                La clave izquierda es menor; se elige la izquierda.
            @else
                La clave derecha es menor; se elige la derecha.
            @endif
            Se incorpora {{ $input[$event['data']['chosen']]['flight_code'] }} (posición {{ $event['data']['chosen'] }}).
        </p>
        <h4>Prefijo combinado después de esta comparación</h4>
        @include('algorithm.partials.sequence', ['positions' => $event['data']['merged'], 'chosen' => $event['data']['chosen']])
        @break
    @case('merge')
        <p>La sublista queda combinada, incluidos los elementos sobrantes. Copiarlos no añade comparaciones.</p>
        @include('algorithm.partials.sequence', ['positions' => $event['data']['positions'], 'chosen' => null])
        @break
    @case('result')
        <p>Este es el resultado final del subconjunto demostrado, en orden ascendente por {{ $keyLabel }}.</p>
        @include('algorithm.partials.sequence', ['positions' => $event['data']['positions'], 'chosen' => null])
        @break
@endswitch
