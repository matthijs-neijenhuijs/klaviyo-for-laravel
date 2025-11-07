<?php

namespace DutchBridge\KlaviyoForLaravel\Jobs;

use DutchBridge\KlaviyoForLaravel\Exceptions\KlaviyoApiException;
use DutchBridge\KlaviyoForLaravel\KlaviyoClient;
use DutchBridge\KlaviyoForLaravel\Support\RetryHelper;
use DutchBridge\KlaviyoForLaravel\TrackEvent;
use GuzzleHttp\Promise\Each;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Pool;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendKlaviyoTrack implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var array|TrackEvent[]
     */
    public array $events;

    /**
     * @var int number of requests to execute concurrently
     */
    public int $concurrency = 5;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 5;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 3;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [1, 5, 10, 30, 60];
    }

    public function __construct(TrackEvent ...$events)
    {
        $this->onQueue(config('klaviyo.queue.default'));
        $this->events = $events;
        $this->tries = config('klaviyo.queue.retry_attempts', 5);
        $this->concurrency = config('klaviyo.http.concurrency', 5);
    }

    public function handle(KlaviyoClient $client): void
    {
        try {
            $requests = function (Pool $pool) use ($client) {
                foreach ($this->events as $event) {
                    yield $pool
                        ->acceptJson()
                        ->asJson()
                        ->withToken($client->getPrivateKey(), 'Klaviyo-API-Key')
                        ->withHeaders([
                            'revision' => $client->getApiVersion()
                        ])
                        ->retry(3, function (int $attempt, \Exception $exception) {
                            return RetryHelper::getRetryDelay($attempt) * 1000; // Convert to milliseconds
                        }, function (\Exception $exception, \Illuminate\Http\Client\Request $request) {
                            return $exception instanceof KlaviyoApiException && 
                                   RetryHelper::shouldRetry($exception->getStatusCode());
                        })
                        ->post($client->getEndpoint().'events', [
                            'data' => $event->toPayload()
                        ]);
                }
            };

            $client->pool(function (Pool $pool) use ($requests) {
                Each::ofLimit(
                    $requests($pool),
                    $this->concurrency,
                    function (\Illuminate\Http\Client\Response $response, $index) {
                        try {
                            RetryHelper::handleApiResponse($response);
                            
                            Log::debug('Klaviyo track event sent successfully', [
                                'event_index' => $index,
                                'status_code' => $response->status(),
                            ]);
                        } catch (KlaviyoApiException $exception) {
                            if (RetryHelper::shouldRetry($exception->getStatusCode())) {
                                throw $exception;
                            }
                            
                            Log::warning('Klaviyo track event failed permanently', [
                                'event_index' => $index,
                                'error' => $exception->getMessage(),
                                'status_code' => $exception->getStatusCode(),
                                'response' => $exception->getResponseData(),
                            ]);
                        }
                    }
                )->wait();
            });

            Log::info('Klaviyo track job completed successfully', [
                'events_count' => count($this->events),
            ]);

        } catch (\Exception $exception) {
            Log::error('Klaviyo track job failed', [
                'events_count' => count($this->events),
                'error' => $exception->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            if ($this->attempts() >= $this->tries) {
                Log::critical('Klaviyo track job failed permanently after all retries', [
                    'events_count' => count($this->events),
                    'error' => $exception->getMessage(),
                    'attempts' => $this->attempts(),
                ]);
            }

            throw $exception;
        }
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'klaviyo_track_' . md5(serialize($this->events));
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Klaviyo track job failed permanently', [
            'events_count' => count($this->events),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
