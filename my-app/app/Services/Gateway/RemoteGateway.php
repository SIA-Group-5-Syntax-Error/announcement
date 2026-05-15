<?php

// Sends HTTP requests to upstream APIs (MockAPI). Used by controllers and the generic gateway route.

namespace App\Services\Gateway;

use App\Support\Gateway\ApiError;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RemoteGateway
{
    // Pass-through: forwards the client request to /api/gateway/{resource}/{path}.
    public function proxy(Request $request, string $resource, ?string $path = null): Response
    {
        $base = $this->routeBase($resource);
        if ($base === null) {
            return ApiError::json('resource_not_found', 'Unknown or unconfigured gateway resource', 404);
        }

        $trail = ($path !== null && $path !== '') ? '/'.ltrim($path, '/') : '';
        $url = rtrim($base, '/').$trail;

        return $this->execute(
            resource: $resource,
            method: strtoupper($request->method()),
            url: $url,
            headers: $this->forwardableRequestHeaders($request),
            bodyResolver: fn (PendingRequest $pending) => $this->sendForIncomingRequest($pending, $request, $url),
        );
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    // Server-side call: builds URL from config and calls upstream (used by AnnouncementController).
    public function resource(string $resource, string $method, string $path = '', ?array $json = null): Response
    {
        $base = $this->routeBase($resource);
        if ($base === null) {
            return ApiError::json('upstream_misconfigured', 'Gateway resource is not configured', 502);
        }

        $url = rtrim($base, '/').($path !== '' ? '/'.ltrim($path, '/') : '');

        return $this->execute(
            resource: $resource,
            method: strtoupper($method),
            url: $url,
            headers: [],
            bodyResolver: fn (PendingRequest $pending) => $this->sendByMethod($pending, strtoupper($method), $url, $json),
        );
    }

    private function execute(
        string $resource,
        string $method,
        string $url,
        array $headers,
        callable $bodyResolver,
    ): Response {
        $started = microtime(true);

        try {
            $pending = $this->pendingClient($headers);
            $clientResponse = $bodyResolver($pending);
            $response = $this->toHttpResponse($clientResponse);

            $this->logRequest($resource, $method, $url, $response->getStatusCode(), $started);

            return $response;
        } catch (ConnectionException $exception) {
            $this->logRequest($resource, $method, $url, 502, $started, $exception->getMessage());

            return ApiError::json('upstream_unreachable', 'Upstream service unreachable', 502);
        }
    }

    private function routeBase(string $resource): ?string
    {
        $base = config('gateway.routes.'.$resource);

        return is_string($base) && $base !== '' ? $base : null;
    }

    private function pendingClient(array $headers): PendingRequest
    {
        $timeout = (int) config('gateway.timeout', 15);
        $retryTimes = (int) config('gateway.retry.times', 0);
        $retrySleep = (int) config('gateway.retry.sleep_ms', 200);

        $pending = Http::timeout($timeout)->withHeaders($headers)->acceptJson();

        if ($retryTimes > 0) {
            $pending = $pending->retry($retryTimes, $retrySleep, throw: false);
        }

        return $pending;
    }

    /**
     * @return array<string, string>
     */
    private function forwardableRequestHeaders(Request $request): array
    {
        $strip = array_map('strtolower', config('gateway.strip_headers', []));
        $out = [];

        foreach ($request->headers->all() as $name => $values) {
            if (in_array(strtolower($name), $strip, true)) {
                continue;
            }
            $out[$name] = $values[0] ?? '';
        }

        return $out;
    }

    private function sendForIncomingRequest(PendingRequest $pending, Request $request, string $url): ClientResponse
    {
        $method = strtoupper($request->method());

        return match ($method) {
            'GET' => $pending->get($url),
            'HEAD' => $pending->head($url),
            'DELETE' => $pending->delete($url),
            'POST' => $pending->post($url, $this->decodedRequestBody($request)),
            'PUT' => $pending->put($url, $this->decodedRequestBody($request)),
            'PATCH' => $pending->patch($url, $this->decodedRequestBody($request)),
            default => $pending->withBody(
                $request->getContent(),
                (string) $request->header('Content-Type', 'application/octet-stream'),
            )->send($method, $url),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function decodedRequestBody(Request $request): array
    {
        if ($request->isJson()) {
            return $request->json()->all();
        }

        return $request->all();
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    private function sendByMethod(PendingRequest $pending, string $method, string $url, ?array $json): ClientResponse
    {
        return match ($method) {
            'GET' => $pending->get($url),
            'HEAD' => $pending->head($url),
            'DELETE' => $pending->delete($url),
            'POST' => $pending->post($url, $json ?? []),
            'PUT' => $pending->put($url, $json ?? []),
            'PATCH' => $pending->patch($url, $json ?? []),
            default => $pending->send($method, $url),
        };
    }

    private function toHttpResponse(ClientResponse $response): Response
    {
        $headers = [];
        foreach ($response->headers() as $name => $lines) {
            $headers[$name] = $lines[0] ?? '';
        }

        return response($response->body(), $response->status(), $headers);
    }

    private function logRequest(
        string $resource,
        string $method,
        string $url,
        int $status,
        float $startedAt,
        ?string $error = null,
    ): void {
        if (! config('gateway.log_requests', true)) {
            return;
        }

        $context = [
            'resource' => $resource,
            'method' => $method,
            'url' => $url,
            'status' => $status,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ];

        if ($error !== null) {
            $context['error'] = $error;
            Log::warning('gateway.request.failed', $context);

            return;
        }

        Log::info('gateway.request', $context);
    }
}
