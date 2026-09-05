@props(['values'])
@php
    $importance = filter_var($values['price_importance'], FILTER_VALIDATE_INT);
    $importance = $importance !== false && $importance >= 0 && $importance <= 100 ? $importance : 50;
@endphp
<div class="sm:col-span-3" style="flex-basis: 100%; min-width: 0">
    <label for="criterion">Ordenar vuelos</label>
    <select id="criterion" name="criterion" class="my-2 rounded border border-slate-400 bg-white p-2" aria-invalid="{{ $errors->has('criterion') ? 'true' : 'false' }}" aria-describedby="criterion-error">
        @foreach (['price' => 'Más barato', 'duration' => 'Más rápido', 'balanced' => 'Mejor equilibrio'] as $key => $label)
            <option value="{{ $key }}" @selected($values['criterion'] === $key)>{{ $label }}</option>
        @endforeach
    </select>
    <p id="criterion-error" class="text-red-700">{{ $errors->first('criterion') }}</p>
    <label for="price-importance">Importancia del precio</label>
    <input id="price-importance" name="price_importance" type="range" min="0" max="100" step="1" value="{{ $importance }}"
           class="my-3 w-full accent-blue-700" aria-describedby="preferences-help importance-error"
           aria-invalid="{{ $errors->has('price_importance') ? 'true' : 'false' }}">
    <output id="weight-display" for="price-importance" aria-live="polite">Precio {{ $importance }} % · Tiempo {{ 100 - $importance }} %</output>
    <p id="importance-error" class="text-red-700">{{ $errors->first('price_importance') }}</p>
    <p id="preferences-help" class="my-2 text-sm text-slate-600">Los pesos solo afectan Mejor equilibrio. Aumentar el precio prioriza el ahorro; aumentar el tiempo prioriza una menor duración. Pulsa Buscar vuelos y aplicar preferencias para actualizar los resultados y la demostración.</p>
</div>
<script>
    (() => {
        const slider = document.getElementById('price-importance');
        slider.addEventListener('input', () => {
            document.getElementById('weight-display').textContent = `Precio ${slider.value} % · Tiempo ${100 - Number(slider.value)} %`;
        });
    })();
</script>
