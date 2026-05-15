<?php

// Registers the "gateway" rate limiter (requests per minute per API key or IP).

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('gateway', function (Request $request) {
            $header = (string) config('gateway.api_key_header', 'X-API-Key');
            $identity = $request->header($header)
                ?? $request->bearerToken()
                ?? $request->ip();

            $maxAttempts = (int) config('gateway.rate_limit.max_attempts', 60);
            $decaySeconds = (int) config('gateway.rate_limit.decay_seconds', 60);

            $limit = $decaySeconds <= 60
                ? Limit::perMinute($maxAttempts)
                : Limit::perMinutes((int) max(1, ceil($decaySeconds / 60)), $maxAttempts);

            return $limit->by((string) $identity);
        });
    }
}
