<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Verify webhook signature
     *
     * @param Request $request
     * @param string $secretKey
     * @return bool
     */
    public function verifySignature(Request $request, string $secretKey): bool
    {
        $signature = $request->header('X-Signature');
        
        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secretKey);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Extract signature from payload
     *
     * @param array $payload
     * @return string|null
     */
    public function extractSignature(array $payload): ?string
    {
        return $payload['signature'] ?? null;
    }

    /**
     * Validate webhook payload structure
     *
     * @param array $payload
     * @return bool
     */
    public function validatePayload(array $payload): bool
    {
        $requiredFields = ['click_id', 'offer_id', 'source', 'timestamp', 'signature'];
        
        foreach ($requiredFields as $field) {
            if (!isset($payload[$field])) {
                Log::warning("Missing required field: {$field}", ['payload' => $payload]);
                return false;
            }
        }

        return true;
    }
}
