<?php

namespace DutchBridge\KlaviyoForLaravel\Support;

use DutchBridge\KlaviyoForLaravel\Contracts\KlaviyoIdentity;
use DutchBridge\KlaviyoForLaravel\KlaviyoClient;
use DutchBridge\KlaviyoForLaravel\TrackEvent;
use Illuminate\Support\Carbon;

/**
 * Fluent API for building and tracking Klaviyo events.
 */
class EventBuilder
{
    protected ?string $eventName = null;
    protected array $properties = [];
    protected KlaviyoIdentity|string|array|null $identity = null;
    protected ?Carbon $timestamp = null;
    protected ?float $value = null;
    protected KlaviyoClient $client;

    public function __construct(KlaviyoClient $client)
    {
        $this->client = $client;
    }

    /**
     * Set the event name.
     *
     * @param string $name
     * @return self
     */
    public function name(string $name): self
    {
        $this->eventName = $name;
        return $this;
    }

    /**
     * Set the user identity for this event.
     *
     * @param KlaviyoIdentity|string|array $identity
     * @return self
     */
    public function forUser(KlaviyoIdentity|string|array $identity): self
    {
        $this->identity = $identity;
        return $this;
    }

    /**
     * Add properties to the event.
     *
     * @param array $properties
     * @return self
     */
    public function withProperties(array $properties): self
    {
        $this->properties = array_merge($this->properties, $properties);
        return $this;
    }

    /**
     * Add a single property to the event.
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function withProperty(string $key, mixed $value): self
    {
        $this->properties[$key] = $value;
        return $this;
    }

    /**
     * Set the event value (typically for revenue tracking).
     *
     * @param float $value
     * @return self
     */
    public function withValue(float $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Set the event timestamp.
     *
     * @param Carbon $timestamp
     * @return self
     */
    public function at(Carbon $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * Set event to occur at a specific time in the past.
     *
     * @param int $minutes
     * @return self
     */
    public function minutesAgo(int $minutes): self
    {
        $this->timestamp = Carbon::now()->subMinutes($minutes);
        return $this;
    }

    /**
     * Set event to occur at a specific time in the past.
     *
     * @param int $hours
     * @return self
     */
    public function hoursAgo(int $hours): self
    {
        $this->timestamp = Carbon::now()->subHours($hours);
        return $this;
    }

    /**
     * Set event to occur at a specific time in the past.
     *
     * @param int $days
     * @return self
     */
    public function daysAgo(int $days): self
    {
        $this->timestamp = Carbon::now()->subDays($days);
        return $this;
    }

    /**
     * Add product information to the event.
     *
     * @param string $productId
     * @param string $productName
     * @param float|null $price
     * @return self
     */
    public function forProduct(string $productId, string $productName, ?float $price = null): self
    {
        $this->withProperty('product_id', $productId);
        $this->withProperty('product_name', $productName);
        
        if ($price !== null) {
            $this->withProperty('price', $price);
            $this->withValue($price);
        }
        
        return $this;
    }

    /**
     * Add category information to the event.
     *
     * @param string $category
     * @return self
     */
    public function inCategory(string $category): self
    {
        $this->withProperty('category', $category);
        return $this;
    }

    /**
     * Add brand information to the event.
     *
     * @param string $brand
     * @return self
     */
    public function forBrand(string $brand): self
    {
        $this->withProperty('brand', $brand);
        return $this;
    }

    /**
     * Build the TrackEvent instance without sending it.
     *
     * @return TrackEvent
     * @throws \InvalidArgumentException
     */
    public function build(): TrackEvent
    {
        if (empty($this->eventName)) {
            throw new \InvalidArgumentException('Event name is required. Use name() method to set it.');
        }

        $properties = $this->properties;
        
        if ($this->value !== null) {
            $properties['value'] = $this->value;
        }

        return new TrackEvent(
            $this->eventName,
            $properties,
            $this->identity,
            $this->timestamp
        );
    }

    /**
     * Build and track the event.
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function track(): void
    {
        $event = $this->build();
        $this->client->track($event);
    }

    /**
     * Build and add to bulk tracking queue.
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function trackBulk(): void
    {
        $event = $this->build();
        $this->client->trackBulk($event);
    }
}