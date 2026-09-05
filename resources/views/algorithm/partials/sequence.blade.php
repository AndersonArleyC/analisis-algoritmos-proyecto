@if ($positions === [])
    <p class="algorithm-demo__empty">Lista vacía</p>
@else
    <ol class="algorithm-demo__sequence">
        @foreach ($positions as $position)
            @php
                $flight = $input[$position];
                $value = $flight[$key];
                $displayValue = match ($key) {
                    'total_price_cop' => number_format($value, 0, ',', '.').' COP',
                    'duration_minutes' => $value.' min',
                    'score' => number_format($value, 6, ',', '.'),
                };
            @endphp
            <li class="algorithm-demo__flight {{ ($chosen ?? null) === $position ? 'algorithm-demo__flight--chosen' : '' }}" data-demo-position="{{ $position }}">
                <span class="algorithm-demo__position">Posición {{ $position }}</span>
                <strong>{{ $flight['flight_code'] }}</strong>
                <span title="Valor recibido: {{ $value }}">{{ $displayValue }}</span>
                @if (($chosen ?? null) === $position)
                    <span class="algorithm-demo__chosen-label">Elegido</span>
                @endif
            </li>
        @endforeach
    </ol>
@endif
