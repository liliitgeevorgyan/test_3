<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Click;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test clicks data
        Click::create([
            'click_id' => 'export_click_1',
            'offer_id' => 12345,
            'source' => 'export_network',
            'timestamp' => Carbon::parse('2024-01-01 10:00:00'),
            'signature' => 'export_sig_1'
        ]);

        Click::create([
            'click_id' => 'export_click_2',
            'offer_id' => 67890,
            'source' => 'export_network_2',
            'timestamp' => Carbon::parse('2024-01-01 11:00:00'),
            'signature' => 'export_sig_2'
        ]);
    }

    /** @test */
    public function it_can_forward_clicks_to_finance_service()
    {
        // Mock successful response from Finance service
        Http::fake([
            'finance-service:8080/*' => Http::response(['status' => 'success'], 200)
        ]);

        $response = $this->postJson('/api/export/forward', [
            'date' => '2024-01-01'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'date' => '2024-01-01'
                ]);

        // Verify the request was made to Finance service
        Http::assertSent(function ($request) {
            return $request->url() === 'http://finance-service:8080/clicks' &&
                   $request['date'] === '2024-01-01' &&
                   $request['total_clicks'] === 2;
        });
    }

    /** @test */
    public function it_handles_finance_service_unavailable()
    {
        // Mock failed response from Finance service
        Http::fake([
            'finance-service:8080/*' => Http::response(['error' => 'Service unavailable'], 503)
        ]);

        $response = $this->postJson('/api/export/forward', [
            'date' => '2024-01-01'
        ]);

        $response->assertStatus(503)
                ->assertJsonStructure(['error']);
    }

    /** @test */
    public function it_validates_date_parameter()
    {
        $response = $this->postJson('/api/export/forward', [
            'date' => 'invalid_date'
        ]);

        $response->assertStatus(400)
                ->assertJsonStructure(['error', 'details']);
    }

    /** @test */
    public function it_prevents_future_dates()
    {
        $futureDate = Carbon::tomorrow()->format('Y-m-d');
        
        $response = $this->postJson('/api/export/forward', [
            'date' => $futureDate
        ]);

        $response->assertStatus(400)
                ->assertJsonStructure(['error', 'details']);
    }

    /** @test */
    public function it_can_check_export_status()
    {
        // Mock successful health check
        Http::fake([
            'finance-service:8080/health' => Http::response(['status' => 'healthy'], 200)
        ]);

        $response = $this->getJson('/api/export/status?' . http_build_query([
            'date' => '2024-01-01'
        ]));

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'data' => [
                        'date' => '2024-01-01',
                        'finance_service_available' => true,
                        'export_ready' => true
                    ]
                ]);
    }

    /** @test */
    public function it_handles_no_clicks_for_date()
    {
        // Mock successful response from Finance service
        Http::fake([
            'finance-service:8080/*' => Http::response(['status' => 'success'], 200)
        ]);

        $response = $this->postJson('/api/export/forward', [
            'date' => '2024-12-31' // Date with no clicks
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'date' => '2024-12-31'
                ]);
    }
}
