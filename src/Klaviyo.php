<?php

namespace DutchBridge\KlaviyoForLaravel;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Http\Client\Response delete(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response get(string $url, array|string|null $query = null)
 * @method static \Illuminate\Http\Client\Response head(string $url, array|string|null $query = null)
 * @method static \Illuminate\Http\Client\Response patch(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response post(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response put(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response send(string $method, string $url, array $options = [])
 * @method static \Illuminate\Http\Client\PendingRequest async(bool $async = true)
 * @method static array pool(callable $callback)
 * 
 * @method static void track(\DutchBridge\KlaviyoForLaravel\TrackEvent ...$events)
 * @method static void trackBulk(\DutchBridge\KlaviyoForLaravel\TrackEvent ...$events)
 * @method static void identify(\DutchBridge\KlaviyoForLaravel\Contracts\KlaviyoIdentity|string|array $identity = null)
 * @method static void identifyBulk(array $profiles)
 * @method static \DutchBridge\KlaviyoForLaravel\Support\EventBuilder event(string $eventName = null)
 * @method static array|null resolveIdentity(\DutchBridge\KlaviyoForLaravel\Contracts\KlaviyoIdentity|string|array|null $identity = null)
 * @method static \DutchBridge\KlaviyoForLaravel\Support\CacheManager cache()
 * @method static \DutchBridge\KlaviyoForLaravel\Support\BulkEventManager bulkEvents()
 * @method static void flushBulkEvents()
 * @method static bool isEnabled()
 * @method static void enable()
 * @method static void disable()
 * @method static void push(...$values)
 * @method static void prepend(...$values)
 * @method static void pushViewed(\DutchBridge\KlaviyoForLaravel\Contracts\ViewedProduct $product)
 */
class Klaviyo extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'klaviyo';
    }
}
