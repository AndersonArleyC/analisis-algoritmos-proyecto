@props(['flight', 'score' => null])

<article class="flight-card rounded-xl border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="flight-{{ $flight->id }}-title">
    <h3 id="flight-{{ $flight->id }}-title" class="text-lg font-semibold text-blue-800">{{ $flight->airline }} · {{ $flight->flight_code }}</h3>
    <p class="my-3 text-xl font-semibold">{{ $flight->origin }} → {{ $flight->destination }}</p>
    <dl class="grid grid-cols-2 gap-4 text-sm">
        <div><dt class="text-slate-600">Salida · Bogotá</dt><dd>{{ $flight->departure_at->setTimezone('America/Bogota')->format('d/m/Y H:i') }}</dd></div>
        <div><dt class="text-slate-600">Llegada · Bogotá</dt><dd>{{ $flight->arrival_at->setTimezone('America/Bogota')->format('d/m/Y H:i') }}</dd></div>
        <div><dt class="text-slate-600">Duración total, incluidas escalas</dt><dd>{{ $flight->duration_minutes }} min</dd></div>
        <div><dt class="text-slate-600">Escalas</dt><dd>{{ $flight->stops }}</dd></div>
        <div class="col-span-2"><dt class="text-slate-600">Equipaje incluido</dt><dd>{{ $flight->baggage_description }}</dd></div>
    </dl>
    <p class="mt-4 text-xl font-bold">$ {{ number_format($flight->total_price_cop, 0, ',', '.') }} COP</p>
    <p class="text-sm text-slate-600">Total por pasajero · Impuestos simulados incluidos</p>
    @if ($score !== null)
        <p class="my-3 font-medium text-blue-800" aria-describedby="score-help">Puntuación de equilibrio: <span title="{{ $score }}">{{ number_format($score, 6, ',', '.') }}</span></p>
    @endif
    <label class="flight-choice mt-4 flex items-center gap-3 rounded bg-blue-50 p-3" hidden>
        <input type="checkbox" class="h-5 w-5 accent-blue-700" data-flight-choice
               data-code="{{ $flight->flight_code }}" data-airline="{{ $flight->airline }}"
               data-price="{{ $flight->total_price_cop }}" data-duration="{{ $flight->duration_minutes }}"
               data-stops="{{ $flight->stops }}" data-baggage="{{ $flight->baggage_description }}"
               aria-describedby="comparison-help" aria-controls="comparison-table"
               aria-label="Comparar vuelo {{ $flight->flight_code }}" autocomplete="off">
        Comparar este vuelo
    </label>
</article>
