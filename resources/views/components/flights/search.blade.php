@props(['errors', 'values', 'availability', 'airports', 'destinations', 'days', 'example'])
<form id="flight-search" method="GET" action="{{ route('flights.index') }}" class="search-box">
    @if ($errors->any())
        <div class="validation-summary" role="alert"><strong>Revisa los campos indicados para realizar la búsqueda.</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <div class="search-fields">
        <div class="search-field">
            <label for="origin">Origen</label>
            <select id="origin" name="origin" required aria-describedby="origin-error" aria-invalid="{{ $errors->has('origin') ? 'true' : 'false' }}" @disabled($availability === [])>
                <option value="">¿Desde dónde sales?</option>
                @foreach ($availability as $code => $routes)
                    <option value="{{ $code }}" @selected($values['origin'] === $code)>{{ $airports[$code] ?? $code }}</option>
                @endforeach
            </select>
            <p id="origin-error" class="field-error">{{ $errors->first('origin') }}</p>
        </div>
        <button id="swap-route" type="button" class="swap-button" aria-label="Intercambiar origen y destino" title="Intercambiar origen y destino" hidden>⇄</button>
        <div class="search-field">
            <label for="destination">Destino</label>
            <select id="destination" name="destination" required @disabled($destinations === []) aria-describedby="destination-hint destination-error" aria-invalid="{{ $errors->has('destination') ? 'true' : 'false' }}">
                <option value="">Elige tu destino</option>
                @foreach ($destinations as $code => $routeDays)
                    <option value="{{ $code }}" @selected($values['destination'] === $code)>{{ $airports[$code] ?? $code }}</option>
                @endforeach
            </select>
            <p id="destination-hint" class="field-hint">{{ $destinations === [] ? 'Primero selecciona un origen.' : 'Destinos con vuelos desde tu origen.' }}</p>
            <p id="destination-error" class="field-error">{{ $errors->first('destination') }}</p>
        </div>
        <div class="search-field date-shortcut">
            <span class="field-label">Fecha de salida</span>
            <a href="#date-picker" id="open-calendar">{{ $values['departure_date'] ?: 'Elige un día disponible' }} <span aria-hidden="true">▦</span></a>
            <span class="field-hint">Consulta el calendario de vuelos</span>
        </div>
        <button id="search-submit" type="submit" name="search" value="1" class="primary-button" @disabled($days === [])>Buscar vuelos <span aria-hidden="true">→</span></button>
    </div>
    <noscript><div class="no-js-help"><p>Selecciona un origen y pulsa Actualizar para cargar destinos; después elige destino y pulsa Actualizar de nuevo para cargar fechas.</p><button type="submit" name="stage" value="availability" formnovalidate class="secondary-button">Actualizar destinos y fechas</button></div></noscript>
    <details id="date-picker" class="date-picker" @if ($values['departure_date'] === '') open @endif>
        <summary>Calendario y fechas disponibles <span aria-hidden="true">⌄</span></summary>
        <div class="date-content">
            <div id="availability-calendar" hidden>
                <div class="calendar-heading"><button type="button" id="previous-month" aria-label="Mes anterior">←</button><h3 id="calendar-month" aria-live="polite"></h3><button type="button" id="next-month" aria-label="Mes siguiente">→</button></div>
                <div class="calendar-weekdays" aria-hidden="true"><span>L</span><span>M</span><span>M</span><span>J</span><span>V</span><span>S</span><span>D</span></div>
                <div id="calendar-days" class="calendar-days" role="group" aria-label="Días del mes"></div>
                <p class="calendar-legend">✓ Día elegido <span>·</span> — Sin vuelos <span>·</span> Precios desde, en COP</p>
            </div>
            <div class="date-options">
                <label for="departure_date">Fechas disponibles</label>
                <select id="departure_date" name="departure_date" required @disabled($days === []) aria-describedby="date-hint departure_date-error" aria-invalid="{{ $errors->has('departure_date') ? 'true' : 'false' }}">
                    <option value="">Selecciona una fecha</option>
                    @foreach ($days as $date => $day)
                        <option value="{{ $date }}" @selected($values['departure_date'] === $date)>{{ $date }} · {{ $day['count'] }} vuelos · Desde $ {{ number_format($day['min_price'], 0, ',', '.') }} COP</option>
                    @endforeach
                </select>
                <p id="date-hint" class="field-hint">{{ $days === [] ? 'Selecciona origen y destino para ver fechas con vuelos.' : 'Solo se muestran días con vuelos para esta ruta.' }}</p>
                <p id="departure_date-error" class="field-error">{{ $errors->first('departure_date') }}</p>
                <p id="date-summary" role="status" class="date-summary">
                    @if (isset($days[$values['departure_date']]))
                        {{ \Carbon\CarbonImmutable::parse($values['departure_date'], 'America/Bogota')->locale('es')->translatedFormat('l j \d\e F \d\e Y') }} · {{ $days[$values['departure_date']]['count'] }} vuelos · Desde $ {{ number_format($days[$values['departure_date']]['min_price'], 0, ',', '.') }} COP
                    @else
                        Elige un día para consultar sus opciones y precio mínimo.
                    @endif
                </p>
            </div>
        </div>
    </details>
    <div class="search-bottom"><p>Datos académicos: también puedes explorar las fechas pasadas cargadas.</p>@if ($example)<a class="example-link" href="{{ route('flights.index', $example) }}">Cargar ejemplo <span aria-hidden="true">↗</span></a>@endif</div>
</form>
