<?php

namespace DutchBridge\KlaviyoForLaravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class KlaviyoWebhookSecurity
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return BaseResponse
     */
    public function handle(Request $request, Closure $next): BaseResponse
    {
        // Check if request is from allowed origins
        if (!$this->isFromAllowedOrigin($request)) {
            Log::warning('Klaviyo webhook rejected - invalid origin', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            return response('Forbidden', 403);
        }

        // Rate limiting
        if ($this->isRateLimited($request)) {
            Log::warning('Klaviyo webhook rejected - rate limited', [
                'ip' => $request->ip(),
            ]);
            
            return response('Too Many Requests', 429);
        }

        return $next($request);
    }

    /**
     * Check if the request is from an allowed origin.
     *
     * @param Request $request
     * @return bool
     */
    protected function isFromAllowedOrigin(Request $request): bool
    {
        $allowedOrigins = config('klaviyo.security.allowed_origins', []);
        
        if (empty($allowedOrigins)) {
            return true; // Allow all if no restrictions configured
        }

        $clientIp = $request->ip();
        
        foreach ($allowedOrigins as $origin) {
            if ($this->ipMatches($clientIp, $origin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP matches the allowed pattern.
     *
     * @param string $ip
     * @param string $pattern
     * @return bool
     */
    protected function ipMatches(string $ip, string $pattern): bool
    {
        // Simple wildcard matching
        if (str_contains($pattern, '*')) {
            $pattern = str_replace('*', '.*', $pattern);
            return (bool) preg_match('/^' . $pattern . '$/', $ip);
        }

        return $ip === $pattern;
    }

    /**
     * Check if the request should be rate limited.
     *
     * @param Request $request
     * @return bool
     */
    protected function isRateLimited(Request $request): bool
    {
        if (!config('klaviyo.rate_limiting.enabled', false)) {
            return false;
        }

        $key = 'klaviyo_webhook_rate_limit:' . $request->ip();
        $maxRequests = config('klaviyo.rate_limiting.max_requests_per_minute', 150);
        $ttl = 60; // 1 minute

        $requests = Cache::get($key, 0);
        
        if ($requests >= $maxRequests) {
            return true;
        }

        Cache::put($key, $requests + 1, $ttl);
        
        return false;
    }
}