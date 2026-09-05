<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AeroCompare · Encuentra tu forma de volar</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <link rel="stylesheet" href="{{ asset('flights/search.css') }}">
    <script src="{{ asset('flights/search.js') }}" defer></script>
</head>
<body>
<a class="skip-link" href="#results">Saltar a los resultados</a>
<header class="site-header shell">
    <a class="brand" href="{{ route('flights.index') }}" aria-label="AeroCompare, inicio"><span class="brand-symbol" aria-hidden="true">↗</span> Aero<span>Compare</span></a>
    <span class="demo-badge">Vuelos y precios de demostración</span>
</header>
<main>
    <section class="hero" aria-labelledby="search-title">
        <div class="shell">
            <p class="eyebrow">EXPLORA COLOMBIA · COMPARA CON CLARIDAD</p>
            <h1 id="search-title">Tu viaje, a tu manera.</h1>
            <p class="hero-intro">Encuentra una opción que se ajuste a tu tiempo y a tu presupuesto.</p>
            <p class="trip-meta">Solo ida <span>·</span> 1 pasajero <span>·</span> Precios en COP</p>
            <x-flights.search :errors="$errors" :values="$values" :availability="$availability" :airports="$airports" :destinations="$destinations" :days="$days" :example="$example" />
        </div>
    </section>
    <div class="shell results-area" id="results">
        @if ($availability === [])
            <section class="empty-state"><h2>Aún no hay vuelos disponibles</h2><p>Cuando se carguen vuelos de demostración, podrás elegir sus rutas y fechas aquí.</p></section>
        @elseif ($searched && ! $errors->any())
            <div class="results-heading">
                <div><p class="eyebrow">OPCIONES PARA TU VIAJE</p><h2>{{ $values['origin'] }} <span aria-label="hacia">→</span> {{ $values['destination'] }}</h2>
                    <p>{{ \Carbon\CarbonImmutable::parse($values['departure_date'], 'America/Bogota')->locale('es')->translatedFormat('l j \d\e F \d\e Y') }}</p></div>
                <span class="count-badge">Resultados: {{ $flights->count() }} vuelos</span>
            </div>
        @else
            <div class="results-heading"><div><p class="eyebrow">SIN FECHAS A CIEGAS</p><h2>Elige una ruta. Descubre cuándo volar.</h2><p>Consulta días, precios mínimos y opciones antes de buscar.</p></div></div>
        @endif

        <x-flights.preferences :errors="$errors" :values="$values" :can-search="$days !== []" />

        @if ($result !== null)
            <p id="ranking-summary" class="ranking-summary">Criterio aplicado: {{ ['price' => 'Más barato', 'duration' => 'Más rápido', 'balanced' => 'Mejor equilibrio'][$result['criterion']] }}.
                Precio {{ round($result['priceWeight'] * 100) }} % · Tiempo {{ round($result['timeWeight'] * 100) }} %.
                Comparaciones del ranking completo ({{ count($result['flights']) }} vuelos): {{ $result['comparisons'] }}.
                El contador de la demostración se muestra por separado.
            </p>
            @if ($result['criterion'] === 'balanced')
                <p id="score-help" class="score-help">Menor puntuación significa mejor equilibrio según los pesos aplicados. Es una regla propia del proyecto: depende de todos los vuelos encontrados y no es un porcentaje de calidad. Se redondea solo para mostrar; comparar selecciones no cambia la puntuación.</p>
            @endif
            @if ($flights->isEmpty())
                <p class="empty-state" role="status">No hay vuelos para la ruta y fecha seleccionadas. Prueba otra búsqueda o carga el ejemplo.</p>
            @else
                <p class="results-note">Horarios de America/Bogota · Precio total con impuestos simulados incluidos</p>
                <div id="flight-results" class="flight-grid grid gap-4">
                    @foreach ($flights as $flight)
                        <x-flights.card :flight="$flight" :score="$scores[$flight->id]['score']" />
                    @endforeach
                </div>
                <x-flights.comparison />
            @endif
            <details class="education" id="algorithm-details">
                <summary><span><span class="eyebrow">DETRÁS DE LOS RESULTADOS</span><strong>Cómo funciona Merge Sort</strong></span><span class="summary-action">Explorar los pasos <span aria-hidden="true">↓</span></span></summary>
                <x-algorithm.demo :demonstration="$result['demonstration']" />
            </details>
        @endif
    </div>
</main>
<footer class="site-footer shell"><span class="brand">AeroCompare</span><p>Un proyecto para aprender a comparar. Datos simulados, sin reservas ni pagos.</p></footer>
<script id="flight-availability" type="application/json">{!! json_encode(['routes' => $availability, 'labels' => $airports], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@if ($result !== null && $flights->isNotEmpty())
    @include('flights.comparison-script')
@endif
</body>
</html>
