<?php

namespace Tests\Feature;

use App\Models\Flight;
use Carbon\CarbonImmutable;
use Database\Seeders\FlightSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FlightSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(FlightSeeder::class);
    }

    public function test_initial_form_does_not_show_results_or_errors(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Vuelos y precios de demostración')
            ->assertSee('Cargar ejemplo')
            ->assertDontSee('Resultados:')
            ->assertViewHas('errors', fn ($errors) => $errors->isEmpty());
    }

    public function test_example_returns_exactly_eight_flights_and_preserves_values(): void
    {
        $this->get($this->searchUrl())->assertOk()
            ->assertSee('Resultados: 8 vuelos')
            ->assertSee('DEMO-A')->assertSee('DEMO-B')->assertSee('DEMO-C')
            ->assertSee('DEMO-H')->assertSee('16/10/2026 01:30')
            ->assertDontSee('DM2-A')->assertDontSee('DMC-A')->assertDontSee('DCB-A')
            ->assertSee('value="BOG"', false)
            ->assertSee('value="MDE"', false)
            ->assertSee('value="2026-10-15"', false)
            ->assertViewHas('flights', fn ($flights) => $flights->count() === 8
                && $flights->every(fn ($flight) => $flight->origin === 'BOG'
                    && $flight->destination === 'MDE'
                    && $flight->departure_at->format('Y-m-d') === '2026-10-15'));
    }

    #[DataProvider('searches')]
    public function test_filters_route_and_date(string $origin, string $destination, string $date, string $prefix): void
    {
        $this->get($this->searchUrl(['origin' => $origin, 'destination' => $destination, 'departure_date' => $date]))
            ->assertOk()->assertViewHas('flights', fn ($flights) => $flights->count() === 8
                && $flights->every(fn ($flight) => str_starts_with($flight->flight_code, $prefix)));
    }

    public static function searches(): array
    {
        return [
            ['BOG', 'MDE', '2026-10-16', 'DM2-'],
            ['MDE', 'CTG', '2026-10-15', 'DMC-'],
            ['CLO', 'BOG', '2026-10-17', 'DCB-'],
        ];
    }

    public function test_departure_day_includes_both_edges_but_excludes_adjacent_days(): void
    {
        $template = Flight::where('flight_code', 'DEMO-B')->firstOrFail();
        foreach ([
            'PREVIOUS' => '2026-10-14 23:59:59',
            'START' => '2026-10-15 00:00:00',
            'END' => '2026-10-15 23:59:59',
            'NEXT' => '2026-10-16 00:00:00',
        ] as $code => $time) {
            $flight = $template->replicate();
            $flight->flight_code = $code;
            $flight->departure_at = CarbonImmutable::parse($time, 'America/Bogota');
            $flight->arrival_at = $flight->departure_at->addMinutes(120);
            $flight->save();
        }

        $this->get($this->searchUrl())->assertOk()
            ->assertViewHas('flights', fn ($flights) => $flights->count() === 10
                && $flights->contains('flight_code', 'START')
                && $flights->contains('flight_code', 'END')
                && ! $flights->contains('flight_code', 'PREVIOUS')
                && ! $flights->contains('flight_code', 'NEXT'));
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_input_shows_spanish_errors_without_results(array $input, string $field, string $message): void
    {
        $this->get($this->searchUrl($input))->assertStatus(422)
            ->assertSee($message)
            ->assertDontSee('Resultados:')
            ->assertViewHas('errors', fn ($errors) => $errors->has($field))
            ->assertViewHas('flights', fn ($flights) => $flights->isEmpty());
    }

    public static function invalidInputs(): array
    {
        return [
            [['origin' => ''], 'origin', 'Ingresa el origen.'],
            [['destination' => ''], 'destination', 'Ingresa el destino.'],
            [['departure_date' => ''], 'departure_date', 'Ingresa la fecha de salida.'],
            [['destination' => 'BOG'], 'destination', 'El origen y el destino deben ser diferentes.'],
            [['origin' => 'Bogotá'], 'origin', 'El origen debe ser un código de tres letras mayúsculas.'],
            [['destination' => 'mde'], 'destination', 'El destino debe ser un código de tres letras mayúsculas.'],
            [['departure_date' => '15/10/2026'], 'departure_date', 'Ingresa una fecha válida con formato AAAA-MM-DD.'],
            [['departure_date' => '2026-02-30'], 'departure_date', 'Ingresa una fecha válida con formato AAAA-MM-DD.'],
            [['origin' => ['BOG']], 'origin', 'El origen debe ser un código de tres letras mayúsculas.'],
            [['departure_date' => ['2026-10-15']], 'departure_date', 'Ingresa una fecha válida con formato AAAA-MM-DD.'],
        ];
    }

    public function test_missing_fields_are_required_when_search_is_submitted(): void
    {
        $this->get('/?search=1')->assertStatus(422)
            ->assertSee('Ingresa el origen.')
            ->assertSee('Ingresa el destino.')
            ->assertSee('Ingresa la fecha de salida.');
        $this->get('/?origin=BOG')->assertStatus(422)->assertSee('value="BOG"', false);
    }

    public function test_invalid_date_is_preserved_in_form(): void
    {
        $this->get($this->searchUrl(['departure_date' => '15/10/2026']))
            ->assertStatus(422)->assertSee('value="15/10/2026"', false)
            ->assertSee('value="BOG"', false)->assertSee('value="MDE"', false);
    }

    public function test_search_with_no_matches_displays_empty_state(): void
    {
        foreach ([['departure_date' => '2026-11-01'], ['origin' => 'MDE', 'destination' => 'BOG']] as $input) {
            $this->get($this->searchUrl($input))->assertOk()
                ->assertSee('No hay vuelos para la ruta y fecha seleccionadas.')
                ->assertViewHas('flights', fn ($flights) => $flights->isEmpty());
        }
    }

    private function searchUrl(array $overrides = []): string
    {
        return '/?'.http_build_query(array_merge([
            'origin' => 'BOG', 'destination' => 'MDE', 'departure_date' => '2026-10-15',
        ], $overrides));
    }
}
