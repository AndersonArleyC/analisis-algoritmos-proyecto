<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Services\FlightAvailability;
use App\Services\FlightRankingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rule;

class FlightSearchController extends Controller
{
    public function index(Request $request, FlightRankingService $rankingService, FlightAvailability $availabilityService): Response
    {
        $input = $request->only(['origin', 'destination', 'departure_date', 'criterion', 'price_importance']);
        $input += ['criterion' => 'price', 'price_importance' => '50'];
        $searched = $request->hasAny(['search', 'origin', 'destination', 'departure_date', 'criterion', 'price_importance']);
        $availability = $availabilityService->summary();
        $browsing = $request->query('stage') === 'availability';
        $attempted = $searched || $browsing;
        $searched = $searched && ! $browsing;
        if ($browsing) {
            $origin = is_string($input['origin'] ?? null) ? $input['origin'] : '';
            $destination = is_string($input['destination'] ?? null) ? $input['destination'] : '';
            $date = is_string($input['departure_date'] ?? null) ? $input['departure_date'] : '';
            if (! isset($availability[$origin][$destination])) {
                $input['destination'] = null;
                $input['departure_date'] = null;
            } elseif (! isset($availability[$origin][$destination][$date])) {
                $input['departure_date'] = null;
            }
        }
        $flights = collect();
        $result = null;
        $validator = Validator::make($attempted ? $input : [], $attempted ? [
            'criterion' => ['bail', 'required', 'string', 'in:price,duration,balanced'],
            'price_importance' => ['bail', 'required', 'integer', 'between:0,100'],
            'origin' => ['bail', 'required', 'string', 'regex:/^[A-Z]{3}$/', Rule::in(array_keys($availability))],
            'destination' => ['bail', $browsing ? 'nullable' : 'required', 'string', 'regex:/^[A-Z]{3}$/', 'different:origin'],
            'departure_date' => ['bail', $browsing ? 'nullable' : 'required', 'string', 'date_format:Y-m-d'],
        ] : [], [
            'criterion.required' => 'Selecciona un criterio de ordenamiento.',
            'criterion.string' => 'Selecciona un criterio válido.',
            'criterion.in' => 'Selecciona un criterio válido.',
            'price_importance.required' => 'Indica la importancia del precio.',
            'price_importance.integer' => 'La importancia del precio debe ser un entero entre 0 y 100.',
            'price_importance.between' => 'La importancia del precio debe estar entre 0 y 100.',
            'origin.in' => 'Este aeropuerto no tiene salidas disponibles.',
            'origin.required' => 'Ingresa el origen.',
            'destination.required' => 'Ingresa el destino.',
            'departure_date.required' => 'Ingresa la fecha de salida.',
            'origin.string' => 'El origen debe ser un código de tres letras mayúsculas.',
            'origin.regex' => 'El origen debe ser un código de tres letras mayúsculas.',
            'destination.string' => 'El destino debe ser un código de tres letras mayúsculas.',
            'destination.regex' => 'El destino debe ser un código de tres letras mayúsculas.',
            'destination.different' => 'El origen y el destino deben ser diferentes.',
            'departure_date.string' => 'Ingresa una fecha válida con formato AAAA-MM-DD.',
            'departure_date.date_format' => 'Ingresa una fecha válida con formato AAAA-MM-DD.',
        ]);

        $validator->after(function ($validator) use ($input, $availability, $searched): void {
            if (! $searched || $validator->errors()->isNotEmpty()) {
                return;
            }
            if (! isset($availability[$input['origin']][$input['destination']])) {
                $validator->errors()->add('destination', 'No hay vuelos disponibles para esta ruta. Selecciona otro destino.');
            } elseif (! isset($availability[$input['origin']][$input['destination']][$input['departure_date']])) {
                $validator->errors()->add('departure_date', 'No hay vuelos disponibles en esta fecha. Elige uno de los días habilitados.');
            }
        });

        if ($searched && $validator->passes()) {
            $validated = $validator->validated();
            $start = CarbonImmutable::createFromFormat('!Y-m-d', $validated['departure_date'], 'America/Bogota');

            // A2 almacena horas locales de Bogotá. El intervalo excluye el día siguiente.
            $flights = Flight::query()
                ->where('origin', $validated['origin'])
                ->where('destination', $validated['destination'])
                ->where('departure_at', '>=', $start->format('Y-m-d H:i:s'))
                ->where('departure_at', '<', $start->addDay()->format('Y-m-d H:i:s'))
                ->get();

            // El servicio recibe arrays simples después del filtro, nunca modelos Eloquent.
            $data = $flights->map(fn (Flight $flight): array => [
                'id' => $flight->id,
                'airline' => $flight->airline,
                'flight_code' => $flight->flight_code,
                'origin' => $flight->origin,
                'destination' => $flight->destination,
                'departure_at' => $flight->departure_at->setTimezone('America/Bogota')->toIso8601String(),
                'arrival_at' => $flight->arrival_at->setTimezone('America/Bogota')->toIso8601String(),
                'duration_minutes' => $flight->duration_minutes,
                'stops' => $flight->stops,
                'baggage_description' => $flight->baggage_description,
                'total_price_cop' => $flight->total_price_cop,
            ])->values()->all();
            $result = $rankingService->rank($data, $validated['criterion'], (int) $validated['price_importance'] / 100, true);

            // Las tarjetas existentes conservan sus modelos, en el orden devuelto por el servicio.
            $byId = $flights->keyBy('id');
            $flights = collect($result['flights'])->map(fn (array $flight): Flight => $byId[$flight['id']]);
        }

        // Una entrada manipulada como array debe producir un error, no romper Blade.
        $values = [];
        foreach (['origin', 'destination', 'departure_date', 'criterion', 'price_importance'] as $field) {
            $values[$field] = is_string($input[$field] ?? null) ? $input[$field] : '';
        }

        $destinations = $availability[$values['origin']] ?? [];
        $days = $destinations[$values['destination']] ?? [];
        // Nunca presentar una fecha antigua como seleccionada si ya no está disponible.
        if (! isset($days[$values['departure_date']])) {
            $values['departure_date'] = '';
        }

        return response()->view('flights.index', [
            'values' => $values,
            'availability' => $availability,
            'airports' => config('airports'),
            'destinations' => $destinations,
            'days' => $days,
            'example' => $availabilityService->example($availability),
            'result' => $result,
            'scores' => collect($result['flights'] ?? [])->keyBy('id'),
            'searched' => $searched,
            'flights' => $flights,
            'errors' => (new ViewErrorBag)->put('default', $validator->errors()),
        ], $validator->fails() ? 422 : 200);
    }
}
