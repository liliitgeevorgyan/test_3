<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Click;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test clicks data
        Click::create([
            'click_id' => 'click_1',
            'offer_id' => 12345,
            'source' => 'network_1',
            'timestamp' => Carbon::parse('2024-01-01 10:00:00'),
            'signature' => 'sig_1'
        ]);

        Click::create([
            'click_id' => 'click_2',
            'offer_id' => 12345,
            'source' => 'network_1',
            'timestamp' => Carbon::parse('2024-01-01 11:00:00'),
            'signature' => 'sig_2'
        ]);

        Click::create([
            'click_id' => 'click_3',
            'offer_id' => 67890,
            'source' => 'network_2',
            'timestamp' => Carbon::parse('2024-01-01 12:00:00'),
            'signature' => 'sig_3'
        ]);
    }

    /** @test */
    public function it_can_get_aggregated_report()
    {
        $response = $this->getJson('/api/reports/aggregated?' . http_build_query([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-01'
        ]));

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'data' => [
                        '*' => [
                            'offer_id',
                            'source',
                            'clicks_count',
                            'date'
                        ]
                    ],
                    'pagination',
                    'filters',
                    'sorting'
                ]);
    }

    /** @test */
    public function it_can_filter_by_offer_id()
    {
        $response = $this->getJson('/api/reports/aggregated?' . http_build_query([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-01',
            'offer_id' => 12345
        ]));

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals(12345, $data[0]['offer_id']);
    }

    /** @test */
    public function it_can_filter_by_source()
    {
        $response = $this->getJson('/api/reports/aggregated?' . http_build_query([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-01',
            'source' => 'network_1'
        ]));

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('network_1', $data[0]['source']);
    }

    /** @test */
    public function it_can_sort_by_clicks_count()
    {
        $response = $this->getJson('/api/reports/aggregated?' . http_build_query([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-01',
            'sort_by' => 'clicks_count',
            'sort_direction' => 'desc'
        ]));

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual($data[1]['clicks_count'], $data[0]['clicks_count']);
    }

    /** @test */
    public function it_validates_date_parameters()
    {
        $response = $this->getJson('/api/reports/aggregated?' . http_build_query([
            'start_date' => 'invalid_date',
            'end_date' => '2024-01-01'
        ]));

        $response->assertStatus(400)
                ->assertJsonStructure(['error', 'details']);
    }

    /** @test */
    public function it_can_get_summary_statistics()
    {
        $response = $this->getJson('/api/reports/summary?' . http_build_query([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-01'
        ]));

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'summary' => [
                        'total_clicks',
                        'unique_offers',
                        'unique_sources',
                        'date_range'
                    ]
                ]);

        $summary = $response->json('summary');
        $this->assertEquals(3, $summary['total_clicks']);
        $this->assertEquals(2, $summary['unique_offers']);
        $this->assertEquals(2, $summary['unique_sources']);
    }
}
