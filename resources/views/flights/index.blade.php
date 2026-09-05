<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buscar vuelos · AeroCompare</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        {{-- Estilos mínimos para ejecutar el buscador antes de compilar los recursos. --}}
        <style>
            body { margin: 0; background: #f8fafc; color: #0f172a; font: 1rem/1.5 system-ui, sans-serif; }
            main { max-width: 72rem; margin: auto; padding: 2rem 1rem; }
            section { margin-top: 1.5rem; padding: 1.5rem; border: 1px solid #cbd5e1; border-radius: .5rem; background: white; }
            h1, a { color: #1d4ed8; }
            form { display: flex; flex-wrap: wrap; gap: 1rem; }
            form > div { flex: 1 1 15rem; }
            label { display: block; font-weight: 600; }
            input { box-sizing: border-box; width: 100%; padding: .6rem; border: 1px solid #64748b; border-radius: .25rem; font: inherit; }
            button { padding: .7rem 1rem; background: #1d4ed8; color: white; border: 0; border-radius: .25rem; font: inherit; cursor: pointer; }
            form a { display: inline-block; margin: .5rem; }
            [role="alert"], [id$="-error"] { color: #b91c1c; }
            [role="region"] { overflow-x: auto; }
            table { width: 100%; border-collapse: collapse; text-align: left; }
            th, td { padding: .75rem; border-bottom: 1px solid #cbd5e1; }
            th { background: #eff6ff; }
            :focus-visible { outline: 2px solid #2563eb; outline-offset: 3px; }
        </style>
    @endif
</head>
<body class="bg-slate-50 text-slate-900">
<main class="mx-auto max-w-6xl space-y-6 px-4 py-10">
    <header>
        <h1 class="text-3xl font-bold text-blue-800">AeroCompare</h1>
        <p class="mt-2">Vuelos y precios de demostración</p>
        <p class="text-sm text-slate-600">Una sola vía · Un pasajero · Precios en COP</p>
    </header>

    <section aria-labelledby="search-title" class="rounded-lg border border-slate-200 bg-white p-6">
        <h2 id="search-title" class="mb-4 text-xl font-semibold">Buscar vuelos</h2>
        @if ($errors->any())
            <p role="alert" class="mb-4 text-red-700">Revisa los campos indicados para realizar la búsqueda.</p>
        @endif
        <form method="GET" action="{{ route('flights.index') }}" class="grid gap-4 sm:grid-cols-3">
            <input type="hidden" name="search" value="1">
            @foreach (['origin' => 'Origen', 'destination' => 'Destino', 'departure_date' => 'Fecha de salida'] as $field => $label)
                <div>
                    <label for="{{ $field }}" class="block font-medium">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="text" required
                           value="{{ $values[$field] }}"
                           placeholder="{{ $field === 'departure_date' ? '2026-10-15' : ($field === 'origin' ? 'BOG' : 'MDE') }}"
                           aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}"
                           aria-describedby="{{ $field }}-hint{{ $errors->has($field) ? ' '.$field.'-error' : '' }}"
                           class="mt-1 w-full rounded border border-slate-400 px-3 py-2 focus:outline-2 focus:outline-blue-600">
                    <p id="{{ $field }}-hint" class="mt-1 text-sm text-slate-600">{{ $field === 'departure_date' ? 'Formato AAAA-MM-DD. Hora de Bogotá.' : 'Código de aeropuerto: tres letras mayúsculas.' }}</p>
                    @error($field)
                        <p id="{{ $field }}-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
            <div class="flex flex-wrap items-center gap-4 sm:col-span-3">
                <button type="submit" class="rounded bg-blue-700 px-5 py-2 font-semibold text-white hover:bg-blue-800 focus:outline-2 focus:outline-offset-2 focus:outline-blue-600">Buscar vuelos</button>
                <a href="{{ route('flights.index', ['origin' => 'BOG', 'destination' => 'MDE', 'departure_date' => '2026-10-15']) }}" class="text-blue-700 underline">Cargar ejemplo</a>
            </div>
        </form>
    </section>

    @if ($searched && ! $errors->any())
        <section aria-labelledby="results-title">
            <h2 id="results-title" class="mb-3 text-xl font-semibold">Resultados: {{ $flights->count() }} vuelos</h2>
            @if ($flights->isEmpty())
                <p role="status">No hay vuelos para la ruta y fecha seleccionadas. Prueba otra búsqueda o carga el ejemplo.</p>
            @else
                <p class="mb-3 text-sm text-slate-600">Horarios de America/Bogota. Precio total por pasajero con impuestos simulados.</p>
                <div class="overflow-x-auto rounded border border-slate-200 bg-white" tabindex="0" role="region" aria-label="Resultados de vuelos">
                    <table class="w-full text-left text-sm">
                        <caption class="sr-only">Vuelos encontrados para {{ $values['origin'] }} a {{ $values['destination'] }} el {{ $values['departure_date'] }}</caption>
                        <thead class="bg-blue-50">
                        <tr>
                            @foreach (['Vuelo', 'Ruta', 'Salida', 'Llegada', 'Duración total', 'Escalas', 'Equipaje', 'Precio COP'] as $heading)
                                <th scope="col" class="px-4 py-3">{{ $heading }}</th>
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($flights as $flight)
                            <tr class="border-t border-slate-200">
                                <td class="px-4 py-3">{{ $flight->airline }} · {{ $flight->flight_code }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $flight->origin }} → {{ $flight->destination }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $flight->departure_at->setTimezone('America/Bogota')->format('d/m/Y H:i') }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $flight->arrival_at->setTimezone('America/Bogota')->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">{{ $flight->duration_minutes }} min</td>
                                <td class="px-4 py-3">{{ $flight->stops }}</td>
                                <td class="px-4 py-3">{{ $flight->baggage_description }}</td>
                                <td class="whitespace-nowrap px-4 py-3">$ {{ number_format($flight->total_price_cop, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @elseif (! $searched)
        <p>Ingresa una ruta y una fecha para consultar los vuelos de demostración.</p>
    @endif
</main>
</body>
</html>
