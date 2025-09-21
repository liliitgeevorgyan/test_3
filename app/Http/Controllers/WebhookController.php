<?php

namespace App\Http\Controllers;

use App\Services\ClickService;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Click service instance
     *
     * @var ClickService
     */
    protected $clickService;

    /**
     * Webhook service instance
     *
     * @var WebhookService
     */
    protected $webhookService;

    /**
     * Create a new controller instance.
     *
     * @param ClickService $clickService
     * @param WebhookService $webhookService
     */
    public function __construct(ClickService $clickService, WebhookService $webhookService)
    {
        $this->clickService = $clickService;
        $this->webhookService = $webhookService;
    }

    /**
     * Handle incoming webhook clicks
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function receiveClick(Request $request): JsonResponse
    {
        try {
            // Get webhook secret key
            $secretKey = config('app.webhook_secret_key');
            
            if (!$secretKey) {
                Log::error('Webhook secret key not configured');
                return response()->json(['error' => 'Server configuration error'], 500);
            }

            // Verify signature
            if (!$this->webhookService->verifySignature($request, $secretKey)) {
                Log::warning('Invalid webhook signature', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Get and validate payload
            $payload = $request->json()->all();
            
            if (!$this->webhookService->validatePayload($payload)) {
                return response()->json(['error' => 'Invalid payload structure'], 400);
            }

            // Process the click
            $success = $this->clickService->processClick($payload);

            if ($success) {
                Log::info('Click received and queued for processing', [
                    'click_id' => $payload['click_id']
                ]);
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Click received and queued for processing',
                    'click_id' => $payload['click_id']
                ], 202);
            } else {
                return response()->json(['error' => 'Failed to process click'], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error in webhook endpoint', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle batch clicks (for high throughput scenarios)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function receiveBatchClicks(Request $request): JsonResponse
    {
        try {
            // Get webhook secret key
            $secretKey = config('app.webhook_secret_key');
            
            if (!$secretKey) {
                return response()->json(['error' => 'Server configuration error'], 500);
            }

            // Verify signature
            if (!$this->webhookService->verifySignature($request, $secretKey)) {
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $payload = $request->json()->all();
            
            if (!isset($payload['clicks']) || !is_array($payload['clicks'])) {
                return response()->json(['error' => 'Invalid batch payload structure'], 400);
            }

            $processedCount = 0;
            $failedCount = 0;

            foreach ($payload['clicks'] as $clickData) {
                if ($this->webhookService->validatePayload($clickData)) {
                    if ($this->clickService->processClick($clickData)) {
                        $processedCount++;
                    } else {
                        $failedCount++;
                    }
                } else {
                    $failedCount++;
                }
            }

            return response()->json([
                'status' => 'success',
                'processed' => $processedCount,
                'failed' => $failedCount,
                'total' => count($payload['clicks'])
            ], 202);

        } catch (\Exception $e) {
            Log::error('Error in batch webhook endpoint', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
