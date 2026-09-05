<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;

class FlightSearchController extends Controller
{
    public function index(Request $request): Response
    {
        $input = $request->only(['origin', 'destination', 'departure_date']);
        $searched = $request->hasAny(['search', 'origin', 'destination', 'departure_date']);
        $flights = collect();
        $validator = Validator::make($searched ? $input : [], $searched ? [
            'origin' => ['bail', 'required', 'string', 'regex:/^[A-Z]{3}$/'],
            'destination' => ['bail', 'required', 'string', 'regex:/^[A-Z]{3}$/', 'different:origin'],
            'departure_date' => ['bail', 'required', 'string', 'date_format:Y-m-d'],
        ] : [], [
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
        }

        // Una entrada manipulada como array debe producir un error, no romper Blade.
        $values = [];
        foreach (['origin', 'destination', 'departure_date'] as $field) {
            $values[$field] = is_string($input[$field] ?? null) ? $input[$field] : '';
        }

        return response()->view('flights.index', [
            'values' => $values,
            'searched' => $searched,
            'flights' => $flights,
            'errors' => (new ViewErrorBag)->put('default', $validator->errors()),
        ], $validator->fails() ? 422 : 200);
    }
}
