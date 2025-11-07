<?php

namespace DutchBridge\KlaviyoForLaravel;

use DutchBridge\KlaviyoForLaravel\Contracts\KlaviyoIdentity;
use DutchBridge\KlaviyoForLaravel\Contracts\ViewedProduct;
use DutchBridge\KlaviyoForLaravel\Exceptions\KlaviyoConfigurationException;
use DutchBridge\KlaviyoForLaravel\Exceptions\KlaviyoIdentityException;
use DutchBridge\KlaviyoForLaravel\Jobs\SendKlaviyoIdentify;
use DutchBridge\KlaviyoForLaravel\Jobs\SendKlaviyoTrack;
use DutchBridge\KlaviyoForLaravel\Support\BulkEventManager;
use DutchBridge\KlaviyoForLaravel\Support\CacheManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Traits\Macroable;

/**
 * @method \Illuminate\Http\Client\Response delete(string $url, array $data = [])
 * @method \Illuminate\Http\Client\Response get(string $url, array|string|null $query = null)
 * @method \Illuminate\Http\Client\Response head(string $url, array|string|null $query = null)
 * @method \Illuminate\Http\Client\Response patch(string $url, array $data = [])
 * @method \Illuminate\Http\Client\Response post(string $url, array $data = [])
 * @method \Illuminate\Http\Client\Response put(string $url, array $data = [])
 * @method \Illuminate\Http\Client\Response send(string $method, string $url, array $options = [])
 * @method \Illuminate\Http\Client\PendingRequest async(bool $async = true)
 * @method array pool(callable $callback)
 **/
class KlaviyoClient
{
    use ForwardsCalls, Macroable {
        __call as macroCall;
    }

    /**
     * @see https://developers.klaviyo.com/en/reference/create_profile
     */
    const SERVER_PROFILE_ATTRIBUTES = [
        'email',
        'phone_number',
        'external_id',
        'anonymous_id',
        '_kx',
        'first_name',
        'last_name',
        'organization',
        'title',
        'image',
        'location',
        'properties',
    ];

    /**
     * API Endpoint.
     *
     * @var string
     */
    protected string $endpoint;

    /**
     * Private API Key.
     *
     * @var string|mixed
     */
    protected string $privateKey;

    /**
     * Public API Key.
     *
     * @var string|mixed
     */
    protected string $publicKey;

    /**
     * Klaviyo API revision to use.
     *
     * @var string|mixed
     */
    protected string $apiVersion;

    /**
     * The key for the identity.
     *
     * @var string
     */
    protected string $identityKeyName;

    /**
     * Attributes used for the identification of an Identify Profile.
     *
     * @var string[]
     */
    protected array $identifyAttributes = [
        'email',
        'id',
        'phone_number',
        '_kx',
    ];

    protected Collection $pushCollection;

    /**
     * Whether Klaviyo is enabled.
     */
    protected bool $enabled = true;

    /**
     * Cache manager instance.
     */
    protected CacheManager $cache;

    /**
     * Bulk event manager instance.
     */
    protected BulkEventManager $bulkEventManager;

    /**
     * Create a new Klaviyo client instance.
     *
     * @param array $config Configuration array containing endpoint, keys, and settings
     * @throws KlaviyoConfigurationException
     */
    public function __construct(array $config)
    {
        $this->endpoint = $config['endpoint'] ?? '';
        $this->privateKey = $config['private_api_key'] ?: throw KlaviyoConfigurationException::missingApiKey();
        $this->publicKey = $config['public_api_key'] ?? '';
        $this->apiVersion = $config['api_version'] ?: throw KlaviyoConfigurationException::missingApiVersion();
        $this->identityKeyName = $config['identity_key_name'] ?: throw KlaviyoConfigurationException::invalidIdentityKeyName();
        $this->enabled = $config['enabled'] ?? true;

        // Validate API key format if validation is enabled
        if (($config['security']['validate_api_keys'] ?? true) && $this->privateKey) {
            $this->validateApiKey($this->privateKey);
        }

        $this->pushCollection = new Collection();
        $this->cache = new CacheManager($config['cache'] ?? []);
        $this->bulkEventManager = new BulkEventManager($config['bulk'] ?? []);
    }

    /**
     * Validate the API key format.
     *
     * @param string $apiKey
     * @throws KlaviyoConfigurationException
     */
    private function validateApiKey(string $apiKey): void
    {
        // Klaviyo private API keys typically start with 'pk_' for public or have specific format
        if (empty($apiKey) || strlen($apiKey) < 10) {
            throw KlaviyoConfigurationException::invalidApiKey();
        }
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * @return string
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    /**
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * @return string
     */
    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }

    /**
     * The key for the identity.
     *
     * @return string
     */
    public function getIdentityKeyName(): string
    {
        return $this->identityKeyName;
    }

    /**
     * Check whether Klaviyo script rendering and server-side jobs is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Enable Klaviyo script rendering and server-side jobs.
     *
     * @return void
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable Klaviyo script rendering and server-side jobs.
     *
     * @return void
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Submit a server-side track event to Klaviyo.
     *
     * @param TrackEvent ...$events Events to track
     * @return void
     */
    public function track(TrackEvent ...$events): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $events = collect($events)
            ->reject(fn($event) => empty($event->identity));

        if ($events->isNotEmpty()) {
            dispatch(new SendKlaviyoTrack(...$events->all()));
        }
    }

    /**
     * Submit a server-side identify event to Klaviyo.
     *
     * @param KlaviyoIdentity|string|array|null $identity User identity to identify
     * @return void
     * @throws KlaviyoIdentityException
     */
    public function identify(KlaviyoIdentity|string|array $identity = null): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $identity = $this->resolveIdentity($identity ?? Auth::user());
        dispatch(new SendKlaviyoIdentify($identity));
    }

    /**
     * Resolve identity or profile of user.
     *
     * @param KlaviyoIdentity|string|array|null $identity Identity to resolve
     * @return array|null Resolved identity array
     * @throws KlaviyoIdentityException
     */
    public function resolveIdentity(KlaviyoIdentity|string|array|null $identity = null): ?array
    {
        if ($identity === null && $this->isIdentified()) {
            return $this->getIdentity();
        }

        $identity = with($identity ?? Auth::user(), function ($value) {
            if ($value instanceof KlaviyoIdentity) {
                return $value->getKlaviyoIdentity();
            } elseif (is_string($value)) {
                return [$this->getIdentityKeyName() => $value];
            } elseif (is_array($value)) {
                return $value;
            } else {
                return null;
            }
        });

        if (empty(array_intersect_key($identity ?? [], array_flip($this->identifyAttributes)))) {
            throw KlaviyoIdentityException::invalidIdentity($this->identifyAttributes);
        }

        return $identity;
    }

    /**
     * Decode the __kla_id cookie.
     *
     * @return array
     */
    public function getDecodedCookie(): array
    {
        return json_decode(base64_decode(Cookie::get('__kla_id', '')), true) ?? [];
    }

    /**
     * Does the \Illuminate\Http\Request cookie contain an $exchange_id?
     *
     * @return bool
     */
    public function isIdentified(): bool
    {
        return $this->getIdentity() !== null;
    }

    /**
     * Retrieve the $exchange_id from cookie.
     *
     * @return array|null
     */
    public function getIdentity(): ?array
    {
        if (! empty($value = Arr::get($this->getDecodedCookie(), '$exchange_id'))) {
            return ['_kx' => $value];
        } elseif (! empty($value = Arr::get($this->getDecodedCookie(), '$email'))) {
            return ['email' => $value];
        } else {
            return null;
        }
    }

    /**
     * @return Collection
     */
    public function getPushCollection(): Collection
    {
        return $this->pushCollection;
    }

    /**
     * Push an event to be rendered by the client to the beginning of the collection.
     *
     * @param mixed ...$values Event arguments (action, event_name, properties)
     * @return void
     * @throws \InvalidArgumentException
     */
    public function prepend(...$values): void
    {
        if (count($values) === 0) {
            throw new \InvalidArgumentException('Not enough arguments for prepend.');
        } elseif (count($values) > 3) {
            throw new \InvalidArgumentException('Too many arguments for prepend.');
        }

        $this->pushCollection->prepend($values);
    }

    /**
     * Push an event to be rendered by the client.
     *
     * @param mixed ...$values Event arguments (action, event_name, properties)
     * @return void
     * @throws \InvalidArgumentException
     */
    public function push(...$values): void
    {
        if (count($values) === 0) {
            throw new \InvalidArgumentException('Not enough arguments for push.');
        } elseif (count($values) > 3) {
            throw new \InvalidArgumentException('Too many arguments for push.');
        }

        $this->pushCollection->push($values);
    }

    /**
     * Push a viewed product event to be rendered by the client.
     *
     * @param ViewedProduct $product
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function pushViewed(ViewedProduct $product)
    {
        $item = $product->getViewedProductProperties();
        $this->push('track', 'Viewed Product', $item);
        $this->push('trackViewedItem', $item);
    }

    protected function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl($this->getEndpoint())
            ->acceptJson()
            ->asJson()
            ->withToken($this->privateKey, 'Klaviyo-API-Key')
            ->withHeaders([
                'revision' => $this->getApiVersion()
            ]);
    }

    /**
     * Get the cache manager instance.
     *
     * @return CacheManager
     */
    public function cache(): CacheManager
    {
        return $this->cache;
    }

    /**
     * Get a cached profile or fetch from API.
     *
     * @param array $identity
     * @return array|null
     */
    public function getCachedProfile(array $identity): ?array
    {
        $cacheKey = $this->cache->profileKey($identity);
        
        return $this->cache->remember($cacheKey, function () use ($identity) {
            // This would typically make an API call to fetch the profile
            // For now, we'll just return the identity as the profile
            return $identity;
        });
    }

    /**
     * Clear cached profile data.
     *
     * @param array $identity
     * @return bool
     */
    public function clearCachedProfile(array $identity): bool
    {
        $cacheKey = $this->cache->profileKey($identity);
        return $this->cache->forget($cacheKey);
    }

    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->forwardCallTo($this->client(), $method, $parameters);
    }
}
