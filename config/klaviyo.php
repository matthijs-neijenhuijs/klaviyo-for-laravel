<?php

return [

    /*
     * Enable or disable Klaviyo script rendering and server-side jobs.
     * Useful for local development.
     */
    'enabled' => (bool)env('KLAVIYO_ENABLED', true),

    /*
     * Klaviyo API endpoint
     */
    'endpoint' => env('KLAVIYO_ENDPOINT', 'https://a.klaviyo.com/api/'),

    /*
     * Klaviyo API version
     */
    'api_version' => env('KLAVIYO_API_VERSION', '2024-05-15'),

    /*
     * API Keys
     */
    'private_api_key' => env('KLAVIYO_PRIVATE_API_KEY', ''),
    'public_api_key' => env('KLAVIYO_PUBLIC_API_KEY', ''),

    /*
     * Default identity key name (email, phone_number, external_id)
     */
    'identity_key_name' => env('KLAVIYO_IDENTITY_KEY_NAME', 'email'),

    /*
     * Queue Configuration
     */
    'queue' => [
        'default' => env('KLAVIYO_QUEUE', 'klaviyo'),
        'batch_size' => (int)env('KLAVIYO_QUEUE_BATCH_SIZE', 100),
        'retry_attempts' => (int)env('KLAVIYO_RETRY_ATTEMPTS', 5),
        'retry_delay' => (int)env('KLAVIYO_RETRY_DELAY', 1), // seconds
    ],

    /*
     * HTTP Client Configuration
     */
    'http' => [
        'timeout' => (int)env('KLAVIYO_TIMEOUT', 30),
        'retry_attempts' => (int)env('KLAVIYO_HTTP_RETRY_ATTEMPTS', 3),
        'retry_delay' => (int)env('KLAVIYO_HTTP_RETRY_DELAY', 1000), // milliseconds
        'concurrency' => (int)env('KLAVIYO_CONCURRENCY', 5),
    ],

    /*
     * Rate Limiting Configuration
     */
    'rate_limiting' => [
        'enabled' => (bool)env('KLAVIYO_RATE_LIMITING_ENABLED', true),
        'max_requests_per_minute' => (int)env('KLAVIYO_MAX_REQUESTS_PER_MINUTE', 150),
        'burst_limit' => (int)env('KLAVIYO_BURST_LIMIT', 75),
    ],

    /*
     * Caching Configuration
     */
    'cache' => [
        'enabled' => (bool)env('KLAVIYO_CACHE_ENABLED', true),
        'ttl' => (int)env('KLAVIYO_CACHE_TTL', 3600), // seconds
        'key_prefix' => env('KLAVIYO_CACHE_KEY_PREFIX', 'klaviyo_'),
        'store' => env('KLAVIYO_CACHE_STORE'), // null = default cache store
    ],

    /*
     * Debug and Development
     */
    'debug' => (bool)env('KLAVIYO_DEBUG', false),

    /*
     * Session Configuration
     */
    'session_key' => env('KLAVIYO_SESSION_KEY', '_klaviyo'),

    /*
     * Event Listeners
     */
    'identify_on_login' => (bool)env('KLAVIYO_IDENTITY_ON_LOGIN', true),

    /*
     * Bulk Operations
     */
    'bulk' => [
        'max_events_per_request' => (int)env('KLAVIYO_BULK_MAX_EVENTS', 100),
        'auto_flush_threshold' => (int)env('KLAVIYO_BULK_AUTO_FLUSH', 50),
    ],

    /*
     * Monitoring and Metrics
     */
    'metrics' => [
        'enabled' => (bool)env('KLAVIYO_METRICS_ENABLED', false),
        'track_api_calls' => (bool)env('KLAVIYO_TRACK_API_CALLS', false),
        'track_queue_performance' => (bool)env('KLAVIYO_TRACK_QUEUE_PERFORMANCE', false),
    ],

    /*
     * Security
     */
    'security' => [
        'validate_api_keys' => (bool)env('KLAVIYO_VALIDATE_API_KEYS', true),
        'webhook_secret' => env('KLAVIYO_WEBHOOK_SECRET', ''),
        'allowed_origins' => array_filter(explode(',', env('KLAVIYO_ALLOWED_ORIGINS', ''))),
    ],

];
