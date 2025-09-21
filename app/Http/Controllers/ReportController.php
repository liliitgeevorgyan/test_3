<?php

namespace App\Http\Controllers;

use App\Services\ClickService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    /**
     * Click service instance
     *
     * @var ClickService
     */
    protected $clickService;

    /**
     * Create a new controller instance.
     *
     * @param ClickService $clickService
     */
    public function __construct(ClickService $clickService)
    {
        $this->clickService = $clickService;
    }

    /**
     * Get aggregated clicks report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAggregatedReport(Request $request): JsonResponse
    {
        try {
            // Validate request parameters
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'offer_id' => 'nullable|integer',
                'source' => 'nullable|string|max:255',
                'sort_by' => 'nullable|in:clicks_count,offer_id,source,date',
                'sort_direction' => 'nullable|in:asc,desc',
                'limit' => 'nullable|integer|min:1|max:1000',
                'page' => 'nullable|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 400);
            }

            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $filters = $request->only(['offer_id', 'source']);
            $sortBy = $request->input('sort_by', 'clicks_count');
            $sortDirection = $request->input('sort_direction', 'desc');
            $limit = $request->input('limit', 100);
            $page = $request->input('page', 1);

            // Get aggregated data
            $data = $this->clickService->getAggregatedData(
                $startDate,
                $endDate,
                $filters,
                $sortBy,
                $sortDirection
            );

            // Apply pagination
            $total = $data->count();
            $offset = ($page - 1) * $limit;
            $paginatedData = $data->slice($offset, $limit)->values();

            return response()->json([
                'status' => 'success',
                'data' => $paginatedData,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit),
                    'from' => $offset + 1,
                    'to' => min($offset + $limit, $total)
                ],
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'offer_id' => $filters['offer_id'] ?? null,
                    'source' => $filters['source'] ?? null
                ],
                'sorting' => [
                    'sort_by' => $sortBy,
                    'sort_direction' => $sortDirection
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate report',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get clicks summary statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSummary(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 400);
            }

            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $totalClicks = $this->clickService->getClicksCount($startDate, $endDate);
            
            $aggregatedData = $this->clickService->getAggregatedData($startDate, $endDate);
            
            $uniqueOffers = $aggregatedData->pluck('offer_id')->unique()->count();
            $uniqueSources = $aggregatedData->pluck('source')->unique()->count();

            return response()->json([
                'status' => 'success',
                'summary' => [
                    'total_clicks' => $totalClicks,
                    'unique_offers' => $uniqueOffers,
                    'unique_sources' => $uniqueSources,
                    'date_range' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate summary',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
