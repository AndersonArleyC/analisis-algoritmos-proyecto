@props(['flight', 'score' => null])

@php
    $departure = $flight->departure_at->setTimezone('America/Bogota');
    $arrival = $flight->arrival_at->setTimezone('America/Bogota');
    $nextDay = $departure->format('Y-m-d') !== $arrival->format('Y-m-d');
@endphp
<article class="flight-card" aria-labelledby="flight-{{ $flight->id }}-title">
    <div class="flight-main">
        <div class="flight-airline"><span class="airline-symbol" aria-hidden="true">↗</span><div><h3 id="flight-{{ $flight->id }}-title">{{ $flight->airline }}</h3><p>{{ $flight->flight_code }}</p></div></div>
        <div class="flight-schedule">
            <div><strong>{{ $departure->format('H:i') }}</strong><span>{{ $flight->origin }}</span><small>Salida {{ $departure->format('d/m/Y H:i') }}</small></div>
            <div class="flight-duration"><span>{{ $flight->duration_minutes }} min</span><div class="route-line" aria-hidden="true">→</div><small>{{ $flight->stops === 0 ? 'Sin escalas' : $flight->stops.($flight->stops === 1 ? ' escala' : ' escalas') }}</small></div>
            <div><strong>{{ $arrival->format('H:i') }} @if ($nextDay)<sup title="Llegada en una fecha posterior">+{{ (int) $departure->startOfDay()->diffInDays($arrival->startOfDay()) }} día</sup>@endif</strong><span>{{ $flight->destination }}</span><small>Llegada {{ $arrival->format('d/m/Y H:i') }}</small></div>
        </div>
        <p class="flight-baggage">Equipaje: {{ $flight->baggage_description }} <span>· Duración total, incluidas escalas</span></p>
    </div>
    <div class="flight-price">
        <span>Total por pasajero</span><p class="price-amount">$ {{ number_format($flight->total_price_cop, 0, ',', '.') }} <small>COP</small></p><small>Impuestos simulados incluidos</small>
        @if ($score !== null)<p class="flight-score" aria-describedby="score-help">Equilibrio: <span title="{{ $score }}">{{ number_format($score, 6, ',', '.') }}</span></p>@endif
    <label class="flight-choice" hidden>
        <input type="checkbox" class="h-5 w-5 accent-blue-700" data-flight-choice
               data-code="{{ $flight->flight_code }}" data-airline="{{ $flight->airline }}"
               data-price="{{ $flight->total_price_cop }}" data-duration="{{ $flight->duration_minutes }}"
               data-stops="{{ $flight->stops }}" data-baggage="{{ $flight->baggage_description }}"
               aria-describedby="comparison-help" aria-controls="comparison-table"
               aria-label="Comparar vuelo {{ $flight->flight_code }}" autocomplete="off">
        Comparar este vuelo
    </label>
    </div>
</article>
