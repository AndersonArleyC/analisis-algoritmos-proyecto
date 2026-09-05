<?php

namespace Tests\Feature;

use App\Models\Flight;
use Database\Seeders\FlightSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlightAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(FlightSeeder::class);
    }

    public function test_summary_comes_from_stored_flights_and_local_departure_days(): void
    {
        $response = $this->get('/')->assertOk();
        $summary = $response->viewData('availability');
        $this->assertEqualsCanonicalizing(['BOG', 'MDE', 'CLO'], array_keys($summary));
        $this->assertSame(['MDE'], array_keys($summary['BOG']));
        $this->assertSame(['CTG'], array_keys($summary['MDE']));
        $this->assertSame(['2026-10-15', '2026-10-16'], array_keys($summary['BOG']['MDE']));
        $this->assertSame(['count' => 8, 'min_price' => 200000], $summary['BOG']['MDE']['2026-10-15']);
        // DEMO-H sale a las 23:30 y llega al día siguiente; cuenta en el día 15.
        $response->assertSee('Bogotá — El Dorado (BOG)');
        Flight::where('flight_code', 'DEMO-A')->update(['total_price_cop' => 100000]);
        $this->assertSame(100000, $this->get('/')->viewData('availability')['BOG']['MDE']['2026-10-15']['min_price']);
    }

    public function test_server_alternative_loads_destinations_then_dates_then_search(): void
    {
        $first = $this->get('/?stage=availability&origin=BOG')->assertOk();
        $this->assertSame(['MDE'], array_keys($first->viewData('destinations')));
        $this->assertSame([], $first->viewData('days'));
        $second = $this->get('/?stage=availability&origin=BOG&destination=MDE&criterion=balanced&price_importance=75')->assertOk();
        $this->assertCount(2, $second->viewData('days'));
        $this->assertSame('balanced', $second->viewData('values')['criterion']);
        $this->assertSame('75', $second->viewData('values')['price_importance']);
        $this->get('/?search=1&origin=BOG&destination=MDE&departure_date=2026-10-15&criterion=balanced&price_importance=75')
            ->assertOk()->assertViewHas('flights', fn ($flights) => $flights->count() === 8);
    }

    public function test_stale_dependent_values_are_cleared_during_server_refresh(): void
    {
        $response = $this->get('/?stage=availability&origin=CLO&destination=MDE&departure_date=2026-10-15')->assertOk();
        $this->assertSame('', $response->viewData('values')['destination']);
        $this->assertSame('', $response->viewData('values')['departure_date']);
        $response = $this->get('/?stage=availability&origin=CLO&destination=BOG&departure_date=2026-10-15')->assertOk();
        $this->assertSame('BOG', $response->viewData('values')['destination']);
        $this->assertSame('', $response->viewData('values')['departure_date']);
    }

    public function test_unknown_airport_and_unavailable_route_are_rejected(): void
    {
        $this->get('/?origin=ZZZ&destination=MDE&departure_date=2026-10-15')->assertStatus(422)
            ->assertViewHas('errors', fn ($errors) => $errors->has('origin'));
        $this->get('/?origin=BOG&destination=CTG&departure_date=2026-10-15')->assertStatus(422)
            ->assertViewHas('errors', fn ($errors) => $errors->has('destination'));
    }

    public function test_empty_database_disables_search_and_has_no_example(): void
    {
        Flight::query()->delete();
        $response = $this->get('/')->assertOk()->assertSee('Aún no hay vuelos disponibles');
        $this->assertSame([], $response->viewData('availability'));
        $this->assertNull($response->viewData('example'));
        $response->assertDontSee('Cargar ejemplo');
    }

    public function test_catalog_falls_back_to_code_and_example_follows_actual_data(): void
    {
        $flight = Flight::firstOrFail()->replicate();
        Flight::query()->delete();
        $flight->origin = 'XYZ';
        $flight->destination = 'BOG';
        $flight->save();
        $response = $this->get('/')->assertOk();
        $this->assertSame('XYZ', $response->viewData('example')['origin']);
        $response->assertSee('value="XYZ"', false)->assertSee('>XYZ</option>', false);
    }
}
