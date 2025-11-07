<?php

namespace DutchBridge\KlaviyoForLaravel\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a Klaviyo webhook is received.
 */
class KlaviyoWebhookReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The webhook payload.
     */
    public array $payload;

    /**
     * The webhook headers.
     */
    public array $headers;

    /**
     * Create a new event instance.
     *
     * @param array $payload
     * @param array $headers
     */
    public function __construct(array $payload, array $headers)
    {
        $this->payload = $payload;
        $this->headers = $headers;
    }

    /**
     * Get the webhook event type.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->payload['type'] ?? null;
    }

    /**
     * Get the webhook timestamp.
     *
     * @return string|null
     */
    public function getTimestamp(): ?string
    {
        return $this->payload['timestamp'] ?? null;
    }

    /**
     * Get specific data from the payload.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getData(string $key, mixed $default = null): mixed
    {
        return data_get($this->payload, $key, $default);
    }

    /**
     * Check if this is a specific event type.
     *
     * @param string $type
     * @return bool
     */
    public function isType(string $type): bool
    {
        return $this->getType() === $type;
    }

    /**
     * Check if this is a profile event.
     *
     * @return bool
     */
    public function isProfileEvent(): bool
    {
        return str_starts_with($this->getType() ?? '', 'profile.');
    }

    /**
     * Check if this is a metric event.
     *
     * @return bool
     */
    public function isMetricEvent(): bool
    {
        return str_starts_with($this->getType() ?? '', 'metric.');
    }

    /**
     * Check if this is a list event.
     *
     * @return bool
     */
    public function isListEvent(): bool
    {
        return str_starts_with($this->getType() ?? '', 'list.');
    }

    /**
     * Check if this is a campaign event.
     *
     * @return bool
     */
    public function isCampaignEvent(): bool
    {
        return str_starts_with($this->getType() ?? '', 'campaign.');
    }
}