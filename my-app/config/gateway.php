<?php

// Gateway settings: API keys, rate limits, MockAPI URLs, timeouts (loaded from .env).

return [

    /*
    |--------------------------------------------------------------------------
    | Upstream HTTP client
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('GATEWAY_HTTP_TIMEOUT', 15),

    'retry' => [
        'times' => (int) env('GATEWAY_RETRY_TIMES', 2),
        'sleep_ms' => (int) env('GATEWAY_RETRY_SLEEP_MS', 200),
    ],

    /*
    |--------------------------------------------------------------------------
    | API key authentication (Postman: X-API-Key or Authorization: Bearer)
    |--------------------------------------------------------------------------
    */
    'api_key_header' => env('GATEWAY_API_KEY_HEADER', 'X-API-Key'),

    'api_keys' => array_values(array_filter(array_map(
        static fn (string $key): string => trim($key),
        explode(',', (string) env('GATEWAY_API_KEYS', '')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Rate limiting (per API key, else per IP)
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'max_attempts' => (int) env('GATEWAY_RATE_LIMIT', 60),
        'decay_seconds' => (int) env('GATEWAY_RATE_LIMIT_DECAY', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request logging
    |--------------------------------------------------------------------------
    */
    'log_requests' => (bool) env('GATEWAY_LOG_REQUESTS', true),

    /*
    |--------------------------------------------------------------------------
    | Upstream route map (resource name => base URL)
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'announcements' => env('MOCKAPI_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Headers never forwarded to upstream services
    |--------------------------------------------------------------------------
    */
    'strip_headers' => [
        'host',
        'connection',
        'content-length',
        'transfer-encoding',
        'x-api-key',
        'authorization',
    ],

];
