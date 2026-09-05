@props(['errors', 'values', 'canSearch'])
@php
    $importance = filter_var($values['price_importance'], FILTER_VALIDATE_INT);
    $importance = $importance !== false && $importance >= 0 && $importance <= 100 ? $importance : 50;
@endphp
<section class="preferences" aria-labelledby="preferences-title">
    <div class="preferences-heading"><h2 id="preferences-title">¿Qué es lo más importante para ti?</h2><p>Ordena las opciones según tu manera de viajar.</p></div>
    <fieldset class="criteria"><legend class="visually-hidden">Criterio de ordenamiento</legend>
        @foreach (['price' => ['Más barato', 'Prioriza el ahorro'], 'duration' => ['Más rápido', 'Menos tiempo de viaje'], 'balanced' => ['Mejor equilibrio', 'Según tus preferencias']] as $key => [$label, $hint])
            <label class="criterion-option"><input type="radio" name="criterion" value="{{ $key }}" form="flight-search" @checked($values['criterion'] === $key) aria-describedby="criterion-error"><span><strong>{{ $label }}</strong><small>{{ $hint }}</small></span></label>
        @endforeach
    </fieldset>
    <p id="criterion-error" class="field-error">{{ $errors->first('criterion') }}</p>
    <div class="weight-row">
        <div class="weight-control"><label for="price-importance">Importancia del precio</label><input id="price-importance" name="price_importance" form="flight-search" type="range" min="0" max="100" step="1" value="{{ $importance }}" aria-describedby="preferences-help importance-error" aria-invalid="{{ $errors->has('price_importance') ? 'true' : 'false' }}"><output id="weight-display" for="price-importance" aria-live="polite">Precio {{ $importance }} % · Tiempo {{ 100 - $importance }} %</output></div>
        <button id="apply-preferences" @disabled(! $canSearch) type="submit" name="search" value="1" form="flight-search" class="secondary-button">Aplicar preferencias</button>
    </div>
    <p id="importance-error" class="field-error">{{ $errors->first('price_importance') }}</p>
    <p id="preferences-help" class="field-hint">Los pesos solo afectan Mejor equilibrio: más precio prioriza el ahorro; más tiempo prioriza una menor duración. Pulsa Aplicar preferencias para actualizar resultados y demostración.</p>
</section>
