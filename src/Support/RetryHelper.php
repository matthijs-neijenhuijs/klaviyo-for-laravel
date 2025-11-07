<?php

namespace DutchBridge\KlaviyoForLaravel\Support;

use DutchBridge\KlaviyoForLaravel\Exceptions\KlaviyoApiException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class RetryHelper
{
    public static function shouldRetry(int $statusCode): bool
    {
        return in_array($statusCode, [429, 500, 502, 503, 504]);
    }

    public static function getRetryDelay(int $attempt): int
    {
        // Exponential backoff: 1s, 2s, 4s, 8s, 16s
        return min(pow(2, $attempt - 1), 16);
    }

    public static function handleApiResponse(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $statusCode = $response->status();
        $responseData = $response->json() ?? [];

        Log::error('Klaviyo API request failed', [
            'status_code' => $statusCode,
            'response' => $responseData,
            'url' => $response->effectiveUri(),
        ]);

        throw KlaviyoApiException::fromResponse($statusCode, $responseData);
    }
}