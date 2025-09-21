<?php

namespace App\Services;

use App\Models\Click;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class FinanceService
{
    /**
     * Finance microservice URL
     *
     * @var string
     */
    private $financeServiceUrl;

    /**
     * Request timeout in seconds
     *
     * @var int
     */
    private $timeout;

    public function __construct()
    {
        $this->financeServiceUrl = config('services.finance.url', 'http://finance-service:8080');
        $this->timeout = config('services.finance.timeout', 30);
    }

    /**
     * Forward clicks data to Finance microservice
     *
     * @param string $date
     * @return bool
     */
    public function forwardClicksForDate(string $date): bool
    {
        try {
            $clicks = Click::getClicksForDate($date);
            
            if ($clicks->isEmpty()) {
                Log::info("No clicks found for date: {$date}");
                return true;
            }

            $payload = $this->formatClicksForFinance($clicks);
            
            $response = Http::timeout($this->timeout)
                ->post($this->financeServiceUrl . '/clicks', $payload);

            if ($response->successful()) {
                Log::info("Successfully forwarded {$clicks->count()} clicks to Finance service for date: {$date}");
                return true;
            } else {
                Log::error("Failed to forward clicks to Finance service", [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'date' => $date
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Exception while forwarding clicks to Finance service", [
                'error' => $e->getMessage(),
                'date' => $date
            ]);
            return false;
        }
    }

    /**
     * Format clicks data for Finance microservice
     *
     * @param Collection $clicks
     * @return array
     */
    private function formatClicksForFinance(Collection $clicks): array
    {
        return [
            'date' => $clicks->first()->timestamp->format('Y-m-d'),
            'total_clicks' => $clicks->count(),
            'clicks' => $clicks->map(function ($click) {
                return [
                    'click_id' => $click->click_id,
                    'offer_id' => $click->offer_id,
                    'source' => $click->source,
                    'timestamp' => $click->timestamp->toISOString(),
                    'signature' => $click->signature,
                ];
            })->toArray()
        ];
    }

    /**
     * Test connection to Finance microservice
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->financeServiceUrl . '/health');
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Failed to connect to Finance service", [
                'error' => $e->getMessage(),
                'url' => $this->financeServiceUrl
            ]);
            return false;
        }
    }
}
