<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Click;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    /** @test */
    public function it_can_receive_a_single_click_webhook()
    {
        $clickData = [
            'click_id' => 'test_click_123',
            'offer_id' => 12345,
            'source' => 'test_network',
            'timestamp' => '2024-01-01T12:00:00Z',
            'signature' => 'test_signature'
        ];

        $response = $this->postJson('/api/webhook/clicks', $clickData);

        $response->assertStatus(202)
                ->assertJson([
                    'status' => 'success',
                    'click_id' => 'test_click_123'
                ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $invalidData = [
            'click_id' => 'test_click_123',
            // Missing required fields
        ];

        $response = $this->postJson('/api/webhook/clicks', $invalidData);

        $response->assertStatus(400)
                ->assertJsonStructure(['error']);
    }

    /** @test */
    public function it_can_receive_batch_clicks()
    {
        $batchData = [
            'clicks' => [
                [
                    'click_id' => 'batch_click_1',
                    'offer_id' => 12345,
                    'source' => 'test_network',
                    'timestamp' => '2024-01-01T12:00:00Z',
                    'signature' => 'test_signature_1'
                ],
                [
                    'click_id' => 'batch_click_2',
                    'offer_id' => 67890,
                    'source' => 'test_network_2',
                    'timestamp' => '2024-01-01T12:01:00Z',
                    'signature' => 'test_signature_2'
                ]
            ]
        ];

        $response = $this->postJson('/api/webhook/clicks/batch', $batchData);

        $response->assertStatus(202)
                ->assertJson([
                    'status' => 'success',
                    'total' => 2
                ]);
    }

    /** @test */
    public function it_validates_timestamp_format()
    {
        $clickData = [
            'click_id' => 'test_click_123',
            'offer_id' => 12345,
            'source' => 'test_network',
            'timestamp' => 'invalid_timestamp',
            'signature' => 'test_signature'
        ];

        $response = $this->postJson('/api/webhook/clicks', $clickData);

        $response->assertStatus(400);
    }

    /** @test */
    public function it_validates_offer_id_is_numeric()
    {
        $clickData = [
            'click_id' => 'test_click_123',
            'offer_id' => 'not_numeric',
            'source' => 'test_network',
            'timestamp' => '2024-01-01T12:00:00Z',
            'signature' => 'test_signature'
        ];

        $response = $this->postJson('/api/webhook/clicks', $clickData);

        $response->assertStatus(400);
    }
}
