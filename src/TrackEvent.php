<?php

namespace DutchBridge\KlaviyoForLaravel;

use DutchBridge\KlaviyoForLaravel\Contracts\KlaviyoIdentity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Represents a tracking event that can be sent to Klaviyo.
 *
 * @property string $id Unique identifier for the event
 * @property array $identity User identity information
 * @property Carbon $timestamp When the event occurred
 * @property string $metric_name Name of the metric/event
 * @property array $payload Event properties and data
 */
class TrackEvent
{
    public string $id;
    public array $identity;
    public Carbon $timestamp;

    /**
     * Create a new track event instance.
     *
     * @param string $metric_name Name of the event/metric
     * @param array $payload Event properties and data
     * @param KlaviyoIdentity|string|array|null $identity User identity
     * @param Carbon|null $time Event timestamp
     */
    public function __construct(
        public string                $metric_name,
        public array                 $payload = [],
        KlaviyoIdentity|string|array $identity = null,
        Carbon                       $time = null
    )
    {
        $this->id = Str::uuid()->toString();
        $this->identity = Klaviyo::resolveIdentity($identity);
        $this->timestamp = $time ?? Carbon::now();
    }

    /**
     * Create a new track event instance using static factory method.
     *
     * @param string $metric_name Name of the event/metric
     * @param array $payload Event properties and data
     * @param KlaviyoIdentity|string|array|null $identity User identity
     * @param Carbon|null $timestamp Event timestamp
     * @return TrackEvent
     */
    public static function make(
        string                       $metric_name,
        array                        $payload = [],
        KlaviyoIdentity|string|array $identity = null,
        Carbon                       $timestamp = null
    ): TrackEvent
    {
        return new static($metric_name, $payload, $identity, $timestamp);
    }

    /**
     * Get the event name/metric name.
     *
     * @return string
     */
    public function getEvent(): string
    {
        return $this->metric_name;
    }

    /**
     * Set the event name/metric name.
     *
     * @param string $event Event name
     * @return TrackEvent
     */
    public function setEvent(string $event): TrackEvent
    {
        $this->metric_name = $event;

        return $this;
    }

    /**
     * Get the event properties.
     *
     * @return array|null
     */
    public function getProperties(): array|null
    {
        return $this->payload;
    }

    /**
     * Set the event properties.
     *
     * @param array|null $properties Event properties
     * @return TrackEvent
     */
    public function setProperties(array|null $properties): TrackEvent
    {
        $this->payload = $properties;

        return $this;
    }

    /**
     * @return KlaviyoIdentity|string|array|null
     */
    public function getIdentity(): KlaviyoIdentity|string|array|null
    {
        return $this->identity;
    }

    /**
     * @param KlaviyoIdentity|string|array|null $identity
     * @return TrackEvent
     */
    public function setIdentity(KlaviyoIdentity|string|array|null $identity): TrackEvent
    {
        $this->identity = Klaviyo::resolveIdentity($identity);

        return $this;
    }

    /**
     * @return Carbon|null
     */
    public function getTimestamp(): Carbon|null
    {
        return $this->timestamp;
    }

    /**
     * @param Carbon|null $timestamp
     * @return TrackEvent
     * @deprecated $timestamp
     */
    public function setTimestamp(Carbon $timestamp = null): TrackEvent
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Convert the event to a Klaviyo API payload.
     *
     * @return array
     */
    public function toPayload(): array
    {
        return [
            'type'       => 'event',
            'attributes' => array_replace_recursive([
                'properties' => [],
                'time'       => $this->timestamp->toIso8601String(),
                'unique_id'  => $this->id,
                'metric'     => [
                    'data' => [
                        'type'       => 'metric',
                        'attributes' => [
                            'name' => $this->metric_name,
                        ]
                    ]
                ],
                'profile'    => [
                    'data' => [
                        'type'       => 'profile',
                        'attributes' => klaviyo_client_to_server_profile($this->identity)
                    ],
                ],
            ], $this->payload),
        ];
    }
}
