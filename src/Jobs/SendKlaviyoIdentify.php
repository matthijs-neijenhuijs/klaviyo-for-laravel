<?php

namespace DutchBridge\KlaviyoForLaravel\Jobs;

use DutchBridge\KlaviyoForLaravel\Exceptions\KlaviyoApiException;
use DutchBridge\KlaviyoForLaravel\KlaviyoClient;
use DutchBridge\KlaviyoForLaravel\Support\RetryHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendKlaviyoIdentify implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 5;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 3;

    public function __construct(protected array $attributes)
    {
        $this->onQueue(config('klaviyo.queue.default'));
        $this->tries = config('klaviyo.queue.retry_attempts', 5);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [1, 5, 10, 30, 60];
    }

    public function handle(KlaviyoClient $client): void
    {
        try {
            $response = $client
                ->retry(3, function (int $attempt, \Exception $exception) {
                    return RetryHelper::getRetryDelay($attempt) * 1000; // Convert to milliseconds
                }, function (\Exception $exception) {
                    return $exception instanceof KlaviyoApiException && 
                           RetryHelper::shouldRetry($exception->getStatusCode());
                })
                ->post('profile-import', [
                    'data' => [
                        'type'       => 'profile',
                        'attributes' => klaviyo_client_to_server_profile($this->attributes)
                    ]
                ]);

            RetryHelper::handleApiResponse($response);

            Log::info('Klaviyo identify completed successfully', [
                'profile_attributes' => $this->attributes,
            ]);

        } catch (KlaviyoApiException $exception) {
            Log::error('Klaviyo identify job failed', [
                'profile_attributes' => $this->attributes,
                'error' => $exception->getMessage(),
                'status_code' => $exception->getStatusCode(),
                'response' => $exception->getResponseData(),
                'attempt' => $this->attempts(),
            ]);

            if ($this->attempts() >= $this->tries) {
                Log::critical('Klaviyo identify job failed permanently after all retries', [
                    'profile_attributes' => $this->attributes,
                    'error' => $exception->getMessage(),
                    'attempts' => $this->attempts(),
                ]);
            }

            throw $exception;
        } catch (\Exception $exception) {
            Log::error('Klaviyo identify job encountered unexpected error', [
                'profile_attributes' => $this->attributes,
                'error' => $exception->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $exception;
        }
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'klaviyo_identify_' . md5(serialize($this->attributes));
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Klaviyo identify job failed permanently', [
            'profile_attributes' => $this->attributes,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
