<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Services\FlightRankingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;

class FlightSearchController extends Controller
{
    public function index(Request $request, FlightRankingService $rankingService): Response
    {
        $input = $request->only(['origin', 'destination', 'departure_date', 'criterion', 'price_importance']);
        $input += ['criterion' => 'price', 'price_importance' => '50'];
        $searched = $request->hasAny(['search', 'origin', 'destination', 'departure_date', 'criterion', 'price_importance']);
        $flights = collect();
        $result = null;
        $validator = Validator::make($searched ? $input : [], $searched ? [
            'criterion' => ['bail', 'required', 'string', 'in:price,duration,balanced'],
            'price_importance' => ['bail', 'required', 'integer', 'between:0,100'],
            'origin' => ['bail', 'required', 'string', 'regex:/^[A-Z]{3}$/'],
            'destination' => ['bail', 'required', 'string', 'regex:/^[A-Z]{3}$/', 'different:origin'],
            'departure_date' => ['bail', 'required', 'string', 'date_format:Y-m-d'],
        ] : [], [
            'criterion.required' => 'Selecciona un criterio de ordenamiento.',
            'criterion.string' => 'Selecciona un criterio válido.',
            'criterion.in' => 'Selecciona un criterio válido.',
            'price_importance.required' => 'Indica la importancia del precio.',
            'price_importance.integer' => 'La importancia del precio debe ser un entero entre 0 y 100.',
            'price_importance.between' => 'La importancia del precio debe estar entre 0 y 100.',
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

        return response()->view('flights.index', [
            'values' => $values,
            'result' => $result,
            'scores' => collect($result['flights'] ?? [])->keyBy('id'),
            'searched' => $searched,
            'flights' => $flights,
            'errors' => (new ViewErrorBag)->put('default', $validator->errors()),
        ], $validator->fails() ? 422 : 200);
    }
}
