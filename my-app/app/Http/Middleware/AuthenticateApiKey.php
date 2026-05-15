<?php

// Blocks /api/* unless X-API-Key or Bearer token matches GATEWAY_API_KEYS in .env.

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKeys = config('gateway.api_keys', []);

        if ($configuredKeys === []) {
            return $this->unauthorized('API key authentication is not configured');
        }

        $provided = $this->extractKey($request);

        if ($provided === null || $provided === '') {
            return $this->unauthorized('API key is required. Send X-API-Key or Authorization: Bearer <key>');
        }

        foreach ($configuredKeys as $validKey) {
            if (hash_equals($validKey, $provided)) {
                $request->attributes->set('gateway_api_key_id', substr(hash('sha256', $validKey), 0, 12));

                return $next($request);
            }
        }

        return $this->unauthorized('Invalid API key');
    }

    private function extractKey(Request $request): ?string
    {
        $headerName = strtolower((string) config('gateway.api_key_header', 'X-API-Key'));
        $fromHeader = $request->header($headerName);
        if (is_string($fromHeader) && $fromHeader !== '') {
            return $fromHeader;
        }

        $authorization = $request->header('Authorization');
        if (is_string($authorization) && str_starts_with($authorization, 'Bearer ')) {
            return trim(substr($authorization, 7));
        }

        return null;
    }

    private function unauthorized(string $message): Response
    {
        return response()->json([
            'error' => [
                'code' => 'unauthorized',
                'message' => $message,
            ],
        ], 401);
    }
}
