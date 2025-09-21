<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ClickService;
use App\Models\Click;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ClickServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $clickService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clickService = new ClickService();
    }

    /** @test */
    public function it_validates_click_data_structure()
    {
        $validData = [
            'click_id' => 'test_123',
            'offer_id' => 12345,
            'source' => 'test_network',
            'timestamp' => '2024-01-01T12:00:00Z',
            'signature' => 'test_signature'
        ];

        $reflection = new \ReflectionClass($this->clickService);
        $method = $reflection->getMethod('validateClickData');
        $method->setAccessible(true);

        $result = $method->invoke($this->clickService, $validData);
        $this->assertTrue($result);
    }

    /** @test */
    public function it_rejects_invalid_click_data()
    {
        $invalidData = [
            'click_id' => 'test_123',
            'offer_id' => 'not_numeric',
            'source' => 'test_network',
            'timestamp' => 'invalid_timestamp',
            'signature' => 'test_signature'
        ];

        $reflection = new \ReflectionClass($this->clickService);
        $method = $reflection->getMethod('validateClickData');
        $method->setAccessible(true);

        $result = $method->invoke($this->clickService, $invalidData);
        $this->assertFalse($result);
    }

    /** @test */
    public function it_gets_aggregated_data()
    {
        // Create test data
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

        $result = $this->clickService->getAggregatedData(
            '2024-01-01',
            '2024-01-01'
        );

        $this->assertCount(1, $result);
        $this->assertEquals(2, $result->first()->clicks_count);
    }

    /** @test */
    public function it_gets_clicks_count_for_date_range()
    {
        // Create test data
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
            'timestamp' => Carbon::parse('2024-01-02 10:00:00'),
            'signature' => 'sig_2'
        ]);

        $count = $this->clickService->getClicksCount('2024-01-01', '2024-01-01');
        $this->assertEquals(1, $count);

        $count = $this->clickService->getClicksCount('2024-01-01', '2024-01-02');
        $this->assertEquals(2, $count);
    }
}
