<?php

namespace DutchBridge\KlaviyoForLaravel\Http\Controllers;

use DutchBridge\KlaviyoForLaravel\Events\KlaviyoWebhookReceived;
use DutchBridge\KlaviyoForLaravel\Exceptions\KlaviyoException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class KlaviyoWebhookController extends Controller
{
    /**
     * Handle incoming Klaviyo webhooks.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            // Verify webhook signature if secret is configured
            if ($this->shouldVerifySignature()) {
                $this->verifySignature($request);
            }

            $payload = $request->all();
            
            Log::info('Klaviyo webhook received', [
                'type' => $payload['type'] ?? 'unknown',
                'timestamp' => $payload['timestamp'] ?? null,
                'headers' => $request->headers->all(),
            ]);

            // Fire Laravel event for webhook processing
            event(new KlaviyoWebhookReceived($payload, $request->headers->all()));

            return response()->json(['status' => 'success']);

        } catch (KlaviyoException $exception) {
            Log::error('Klaviyo webhook processing failed', [
                'error' => $exception->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage()
            ], 400);

        } catch (\Exception $exception) {
            Log::error('Klaviyo webhook processing failed with unexpected error', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Check if webhook signature verification should be performed.
     *
     * @return bool
     */
    protected function shouldVerifySignature(): bool
    {
        return !empty(config('klaviyo.security.webhook_secret'));
    }

    /**
     * Verify the webhook signature.
     *
     * @param Request $request
     * @throws KlaviyoException
     */
    protected function verifySignature(Request $request): void
    {
        $secret = config('klaviyo.security.webhook_secret');
        $signature = $request->header('X-Klaviyo-Signature');
        
        if (empty($signature)) {
            throw new KlaviyoException('Webhook signature missing');
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            throw new KlaviyoException('Invalid webhook signature');
        }
    }
}