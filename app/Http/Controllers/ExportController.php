<?php

namespace App\Http\Controllers;

use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ExportController extends Controller
{
    /**
     * Finance service instance
     *
     * @var FinanceService
     */
    protected $financeService;

    /**
     * Create a new controller instance.
     *
     * @param FinanceService $financeService
     */
    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    /**
     * Forward clicks data to Finance microservice
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forward(Request $request): JsonResponse
    {
        try {
            // Validate request parameters
            $validator = Validator::make($request->all(), [
                'date' => 'required|date|before_or_equal:today'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 400);
            }

            $date = $request->input('date');

            // Test connection to Finance service first
            if (!$this->financeService->testConnection()) {
                return response()->json([
                    'error' => 'Finance service is not available',
                    'message' => 'Unable to connect to Finance microservice'
                ], 503);
            }

            // Forward clicks data
            $success = $this->financeService->forwardClicksForDate($date);

            if ($success) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Clicks data successfully forwarded to Finance service',
                    'date' => $date
                ]);
            } else {
                return response()->json([
                    'error' => 'Failed to forward clicks data',
                    'message' => 'An error occurred while sending data to Finance service'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get export status for a specific date
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date' => 'required|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 400);
            }

            $date = $request->input('date');

            // Check if Finance service is available
            $isAvailable = $this->financeService->testConnection();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'date' => $date,
                    'finance_service_available' => $isAvailable,
                    'export_ready' => $isAvailable
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to check export status',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
