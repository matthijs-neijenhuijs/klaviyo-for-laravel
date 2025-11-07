<?php

namespace DutchBridge\KlaviyoForLaravel\Support;

use DutchBridge\KlaviyoForLaravel\TrackEvent;
use Illuminate\Support\Collection;

class BulkEventManager
{
    protected Collection $events;
    protected int $maxEventsPerRequest;
    protected int $autoFlushThreshold;

    public function __construct(array $config = [])
    {
        $this->events = new Collection();
        $this->maxEventsPerRequest = $config['max_events_per_request'] ?? 100;
        $this->autoFlushThreshold = $config['auto_flush_threshold'] ?? 50;
    }

    /**
     * Add an event to the bulk collection.
     *
     * @param TrackEvent $event
     * @return self
     */
    public function add(TrackEvent $event): self
    {
        $this->events->push($event);

        // Auto-flush if threshold is reached
        if ($this->events->count() >= $this->autoFlushThreshold) {
            $this->flush();
        }

        return $this;
    }

    /**
     * Add multiple events to the bulk collection.
     *
     * @param array|Collection $events
     * @return self
     */
    public function addMany($events): self
    {
        $events = collect($events);
        
        foreach ($events as $event) {
            if ($event instanceof TrackEvent) {
                $this->events->push($event);
            }
        }

        // Auto-flush if threshold is reached
        if ($this->events->count() >= $this->autoFlushThreshold) {
            $this->flush();
        }

        return $this;
    }

    /**
     * Get all pending events.
     *
     * @return Collection
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    /**
     * Get the number of pending events.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->events->count();
    }

    /**
     * Check if there are pending events.
     *
     * @return bool
     */
    public function hasEvents(): bool
    {
        return $this->events->isNotEmpty();
    }

    /**
     * Chunk events into batches for processing.
     *
     * @return Collection
     */
    public function chunk(): Collection
    {
        return $this->events->chunk($this->maxEventsPerRequest);
    }

    /**
     * Clear all pending events.
     *
     * @return self
     */
    public function clear(): self
    {
        $this->events = new Collection();
        return $this;
    }

    /**
     * Flush all pending events and return batches.
     *
     * @return Collection
     */
    public function flush(): Collection
    {
        $batches = $this->chunk();
        $this->clear();
        return $batches;
    }

    /**
     * Get events that should be processed immediately.
     *
     * @return Collection
     */
    public function getReadyEvents(): Collection
    {
        if ($this->events->count() >= $this->maxEventsPerRequest) {
            return $this->flush();
        }

        return new Collection();
    }
}