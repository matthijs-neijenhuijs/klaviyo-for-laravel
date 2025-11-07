<?php

namespace DutchBridge\KlaviyoForLaravel\Support;

use Illuminate\Support\Facades\Cache;

class CacheManager
{
    protected string $store;
    protected string $prefix;
    protected int $ttl;
    protected bool $enabled;

    public function __construct(array $config = [])
    {
        $this->enabled = $config['enabled'] ?? false;
        $this->store = $config['store'] ?? config('cache.default');
        $this->prefix = $config['key_prefix'] ?? 'klaviyo_';
        $this->ttl = $config['ttl'] ?? 3600;
    }

    /**
     * Get an item from the cache.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->enabled) {
            return $default;
        }

        return Cache::store($this->store)->get($this->prefix . $key, $default);
    }

    /**
     * Store an item in the cache.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        return Cache::store($this->store)->put(
            $this->prefix . $key,
            $value,
            $ttl ?? $this->ttl
        );
    }

    /**
     * Get an item from cache or execute callback and cache result.
     *
     * @param string $key
     * @param \Closure $callback
     * @param int|null $ttl
     * @return mixed
     */
    public function remember(string $key, \Closure $callback, ?int $ttl = null): mixed
    {
        if (!$this->enabled) {
            return $callback();
        }

        return Cache::store($this->store)->remember(
            $this->prefix . $key,
            $ttl ?? $this->ttl,
            $callback
        );
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }

        return Cache::store($this->store)->forget($this->prefix . $key);
    }

    /**
     * Flush all cache items with the configured prefix.
     * Note: This implementation clears the entire cache store.
     * For production use, consider using cache tags for more granular control.
     *
     * @return bool
     */
    public function flush(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        // Clear the entire cache store - consider using tags for more granular control
        return Cache::store($this->store)->clear();
    }

    /**
     * Generate a cache key for profile data.
     *
     * @param array $identity
     * @return string
     */
    public function profileKey(array $identity): string
    {
        return 'profile_' . md5(serialize($identity));
    }

    /**
     * Generate a cache key for API responses.
     *
     * @param string $endpoint
     * @param array $params
     * @return string
     */
    public function apiKey(string $endpoint, array $params = []): string
    {
        return 'api_' . md5($endpoint . serialize($params));
    }
}