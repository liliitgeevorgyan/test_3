<?php

namespace App\Services;

use App\Models\Click;
use App\Jobs\ProcessClickJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class ClickService
{
    /**
     * Process incoming click data
     *
     * @param array $clickData
     * @return bool
     */
    public function processClick(array $clickData): bool
    {
        try {
            // Validate click data
            if (!$this->validateClickData($clickData)) {
                Log::warning('Invalid click data received', ['data' => $clickData]);
                return false;
            }

            // Dispatch job for async processing to handle high throughput
            ProcessClickJob::dispatch($clickData);

            return true;
        } catch (\Exception $e) {
            Log::error('Error processing click', [
                'error' => $e->getMessage(),
                'data' => $clickData
            ]);
            return false;
        }
    }

    /**
     * Validate click data structure
     *
     * @param array $data
     * @return bool
     */
    private function validateClickData(array $data): bool
    {
        $requiredFields = ['click_id', 'offer_id', 'source', 'timestamp', 'signature'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }

        // Validate offer_id is numeric
        if (!is_numeric($data['offer_id'])) {
            return false;
        }

        // Validate timestamp format
        try {
            new \DateTime($data['timestamp']);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Get aggregated clicks data
     *
     * @param string $startDate
     * @param string $endDate
     * @param array $filters
     * @param string $sortBy
     * @param string $sortDirection
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAggregatedData(
        string $startDate,
        string $endDate,
        array $filters = [],
        string $sortBy = 'clicks_count',
        string $sortDirection = 'desc'
    ) {
        $query = Click::selectRaw('offer_id, source, COUNT(*) as clicks_count, DATE(timestamp) as date')
            ->dateRange($startDate, $endDate);

        // Apply filters
        if (isset($filters['offer_id'])) {
            $query->byOffer($filters['offer_id']);
        }

        if (isset($filters['source'])) {
            $query->bySource($filters['source']);
        }

        // Group by offer_id and source
        $query->groupBy('offer_id', 'source', 'date');

        // Apply sorting
        $query->orderBy($sortBy, $sortDirection);

        return $query->get();
    }

    /**
     * Get clicks for a specific date
     *
     * @param string $date
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getClicksForDate(string $date)
    {
        return Click::getClicksForDate($date);
    }

    /**
     * Get clicks count for a date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return int
     */
    public function getClicksCount(string $startDate, string $endDate): int
    {
        return Click::dateRange($startDate, $endDate)->count();
    }
}
