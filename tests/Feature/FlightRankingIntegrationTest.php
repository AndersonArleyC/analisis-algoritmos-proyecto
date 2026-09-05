<?php

namespace Tests\Feature;

use App\Models\Flight;
use App\Services\FlightRankingService;
use Database\Seeders\FlightSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FlightRankingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(FlightSeeder::class);
    }

    #[DataProvider('criteriaAndWeights')]
    public function test_ranking_and_demo_match_the_filtered_search(string $criterion, int $importance, string $key): void
    {
        $input = Flight::where('origin', 'BOG')->where('destination', 'MDE')
            ->whereDate('departure_at', '2026-10-15')->get()->toArray();
        $expected = app(FlightRankingService::class)->rank($input, $criterion, $importance / 100, true);
        $response = $this->get($this->url(['criterion' => $criterion, 'price_importance' => $importance]))->assertOk();
        $result = $response->viewData('result');
        $this->assertSame($expected, $result);
        $this->assertSame(array_column($expected['flights'], 'id'), $response->viewData('flights')->pluck('id')->all());
        $this->assertSame($key, $result['demonstration']['key']);
        $response->assertSee('Cómo funciona Merge Sort')->assertSee('Comparaciones del ranking completo (8 vuelos):');
        foreach ($result['demonstration']['trace'] as $event) {
            $response->assertSee('data-demo-step="'.$event['step'].'"', false);
        }
        $previous = null;
        foreach ($result['flights'] as $flight) {
            if ($previous !== null) {
                $this->assertLessThanOrEqual($flight[$key], $previous);
            }
            $previous = $flight[$key];
        }
    }

    public static function criteriaAndWeights(): array
    {
        return [
            ['price', 50, 'total_price_cop'], ['duration', 50, 'duration_minutes'],
            ['balanced', 0, 'score'], ['balanced', 50, 'score'], ['balanced', 100, 'score'],
        ];
    }

    public function test_defaults_and_abc_balance(): void
    {
        $default = $this->get($this->url())->assertOk()->viewData('result');
        $this->assertSame('price', $default['criterion']);
        $this->assertSame(0.5, $default['priceWeight']);
        $response = $this->get($this->url(['criterion' => 'balanced']))->assertOk();
        $ranked = collect($response->viewData('result')['flights'])->keyBy('flight_code');
        $this->assertEqualsWithDelta(11 / 60, $ranked['DEMO-C']['score'], 1e-12);
        $this->assertSame(0.5, $ranked['DEMO-A']['score']);
        $this->assertSame(0.5, $ranked['DEMO-B']['score']);
        $this->assertSame('DEMO-C', $response->viewData('flights')->first()->flight_code);
        $response->assertSee('0,183333')->assertSee('no es un porcentaje de calidad');
    }

    #[DataProvider('invalidPreferences')]
    public function test_invalid_preferences_are_rejected(array $options, string $field): void
    {
        $this->get($this->url($options))->assertStatus(422)
            ->assertViewHas('errors', fn ($errors) => $errors->has($field))
            ->assertViewHas('result', null)->assertDontSee('data-algorithm-demo', false);
    }

    public static function invalidPreferences(): array
    {
        return [
            [['criterion' => 'unknown'], 'criterion'], [['criterion' => ['price']], 'criterion'],
            [['criterion' => ''], 'criterion'], [['price_importance' => -1], 'price_importance'],
            [['price_importance' => 101], 'price_importance'], [['price_importance' => 50.5], 'price_importance'],
            [['price_importance' => 'NaN'], 'price_importance'], [['price_importance' => ['50']], 'price_importance'],
            [['price_importance' => ''], 'price_importance'],
        ];
    }

    public function test_full_ranking_and_limited_demo_use_global_normalization(): void
    {
        $template = Flight::where('flight_code', 'DEMO-A')->firstOrFail();
        foreach (['EXTRA-1', 'EXTRA-2'] as $code) {
            $extra = $template->replicate();
            $extra->flight_code = $code;
            $extra->total_price_cop = 1000000;
            $extra->save();
        }
        $response = $this->get($this->url(['criterion' => 'balanced', 'price_importance' => 25]))->assertOk();
        $result = $response->viewData('result');
        $this->assertCount(10, $result['flights']);
        $this->assertCount(8, $result['demonstration']['input']);
        $this->assertSame(10, $result['demonstration']['total_results']);
        $this->assertSame(1000000, $result['normalization']['max_price']);
        $scores = collect($result['flights'])->keyBy('id');
        foreach ($result['demonstration']['input'] as $flight) {
            $this->assertSame($scores[$flight['id']]['score'], $flight['score']);
        }
        $response->assertSee('Demostración de los primeros 8 vuelos de 10 resultados')
            ->assertSee('Comparaciones del ranking completo (10 vuelos):');
        $this->assertSame(0.25, $result['priceWeight']);
    }

    public function test_empty_search_keeps_empty_demo_and_zero_counters(): void
    {
        $response = $this->get($this->url(['departure_date' => '2026-11-01', 'criterion' => 'balanced']))->assertOk();
        $result = $response->viewData('result');
        $this->assertSame([], $result['flights']);
        $this->assertSame(0, $result['comparisons']);
        $this->assertSame(0, $result['demonstration']['comparisons']);
        $this->assertSame(['input', 'result'], array_column($result['demonstration']['trace'], 'type'));
        $response->assertSee('No hay vuelos para la ruta y fecha seleccionadas.');
    }

    private function url(array $options = []): string
    {
        return '/?'.http_build_query(array_merge([
            'origin' => 'BOG', 'destination' => 'MDE', 'departure_date' => '2026-10-15',
        ], $options));
    }
}
