<?php

/**
 * Example script to test the Clicks Service API
 * 
 * Usage: php examples/test_api.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ClicksServiceTester
{
    private $client;
    private $baseUrl;
    private $webhookSecret;

    public function __construct($baseUrl = 'http://localhost:8081', $webhookSecret = 'test_secret_key')
    {
        $this->baseUrl = $baseUrl;
        $this->webhookSecret = $webhookSecret;
        $this->client = new Client([
            'base_uri' => $baseUrl,
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);
    }

    /**
     * Generate HMAC signature for webhook requests
     */
    private function generateSignature($payload)
    {
        return 'sha256=' . hash_hmac('sha256', $payload, $this->webhookSecret);
    }

    /**
     * Test health check endpoint
     */
    public function testHealthCheck()
    {
        echo "Testing health check...\n";
        
        try {
            $response = $this->client->get('/api/health');
            $data = json_decode($response->getBody(), true);
            
            echo "✓ Health check passed: " . $data['status'] . "\n";
            return true;
        } catch (RequestException $e) {
            echo "✗ Health check failed: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Test single click webhook
     */
    public function testSingleClickWebhook()
    {
        echo "Testing single click webhook...\n";
        
        $clickData = [
            'click_id' => 'test_click_' . time(),
            'offer_id' => 12345,
            'source' => 'test_network',
            'timestamp' => date('c'),
            'signature' => 'test_signature'
        ];

        $payload = json_encode($clickData);
        $signature = $this->generateSignature($payload);

        try {
            $response = $this->client->post('/api/webhook/clicks', [
                'body' => $payload,
                'headers' => [
                    'X-Signature' => $signature
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            echo "✓ Single click webhook passed: " . $data['message'] . "\n";
            return true;
        } catch (RequestException $e) {
            echo "✗ Single click webhook failed: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Test batch clicks webhook
     */
    public function testBatchClicksWebhook()
    {
        echo "Testing batch clicks webhook...\n";
        
        $batchData = [
            'clicks' => [
                [
                    'click_id' => 'batch_click_1_' . time(),
                    'offer_id' => 12345,
                    'source' => 'test_network_1',
                    'timestamp' => date('c'),
                    'signature' => 'test_signature_1'
                ],
                [
                    'click_id' => 'batch_click_2_' . time(),
                    'offer_id' => 67890,
                    'source' => 'test_network_2',
                    'timestamp' => date('c'),
                    'signature' => 'test_signature_2'
                ]
            ]
        ];

        $payload = json_encode($batchData);
        $signature = $this->generateSignature($payload);

        try {
            $response = $this->client->post('/api/webhook/clicks/batch', [
                'body' => $payload,
                'headers' => [
                    'X-Signature' => $signature
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            echo "✓ Batch clicks webhook passed: " . $data['processed'] . " processed, " . $data['failed'] . " failed\n";
            return true;
        } catch (RequestException $e) {
            echo "✗ Batch clicks webhook failed: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Test aggregated report endpoint
     */
    public function testAggregatedReport()
    {
        echo "Testing aggregated report...\n";
        
        $params = [
            'start_date' => date('Y-m-d', strtotime('-7 days')),
            'end_date' => date('Y-m-d'),
            'limit' => 10
        ];

        try {
            $response = $this->client->get('/api/reports/aggregated?' . http_build_query($params));
            $data = json_decode($response->getBody(), true);
            
            echo "✓ Aggregated report passed: " . count($data['data']) . " records returned\n";
            return true;
        } catch (RequestException $e) {
            echo "✗ Aggregated report failed: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Test summary endpoint
     */
    public function testSummaryReport()
    {
        echo "Testing summary report...\n";
        
        $params = [
            'start_date' => date('Y-m-d', strtotime('-7 days')),
            'end_date' => date('Y-m-d')
        ];

        try {
            $response = $this->client->get('/api/reports/summary?' . http_build_query($params));
            $data = json_decode($response->getBody(), true);
            
            echo "✓ Summary report passed: " . $data['summary']['total_clicks'] . " total clicks\n";
            return true;
        } catch (RequestException $e) {
            echo "✗ Summary report failed: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Test export status endpoint
     */
    public function testExportStatus()
    {
        echo "Testing export status...\n";
        
        $params = [
            'date' => date('Y-m-d')
        ];

        try {
            $response = $this->client->get('/api/export/status?' . http_build_query($params));
            $data = json_decode($response->getBody(), true);
            
            echo "✓ Export status passed: Finance service available: " . ($data['data']['finance_service_available'] ? 'Yes' : 'No') . "\n";
            return true;
        } catch (RequestException $e) {
            echo "✗ Export status failed: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Run all tests
     */
    public function runAllTests()
    {
        echo "Starting Clicks Service API Tests...\n";
        echo "=====================================\n\n";

        $tests = [
            'testHealthCheck',
            'testSingleClickWebhook',
            'testBatchClicksWebhook',
            'testAggregatedReport',
            'testSummaryReport',
            'testExportStatus'
        ];

        $passed = 0;
        $total = count($tests);

        foreach ($tests as $test) {
            if ($this->$test()) {
                $passed++;
            }
            echo "\n";
        }

        echo "=====================================\n";
        echo "Test Results: {$passed}/{$total} tests passed\n";
        
        if ($passed === $total) {
            echo "🎉 All tests passed!\n";
        } else {
            echo "❌ Some tests failed. Check the output above.\n";
        }

        return $passed === $total;
    }
}

// Run tests if script is executed directly
if (php_sapi_name() === 'cli') {
    $tester = new ClicksServiceTester();
    $tester->runAllTests();
}
