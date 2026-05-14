<?php

namespace App\Services\Gateway;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class RemoteGateway
{
    private const STRIP_REQUEST_HEADERS = [
        'host',
        'connection',
        'content-length',
        'transfer-encoding',
    ];

    public function proxy(Request $request, string $resource, ?string $path = null): Response
    {
        $base = config('services.gateway.routes.'.$resource);
        if (! is_string($base) || $base === '') {
            return response()->json(['error' => 'Unknown or unconfigured gateway resource'], 404);
        }

        $trail = ($path !== null && $path !== '') ? '/'.ltrim($path, '/') : '';
        $url = rtrim($base, '/').$trail;

        try {
            $pending = $this->pendingClient($this->forwardableRequestHeaders($request));
            $clientResponse = $this->sendForIncomingRequest($pending, $request, $url);

            return $this->toHttpResponse($clientResponse);
        } catch (ConnectionException) {
            return response()->json(['error' => 'Upstream service unreachable'], 502);
        }
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    public function resource(string $resource, string $method, string $path = '', ?array $json = null): Response
    {
        $base = config('services.gateway.routes.'.$resource);
        if (! is_string($base) || $base === '') {
            return response()->json(['error' => 'Unknown or unconfigured gateway resource'], 502);
        }

        $url = rtrim($base, '/').($path !== '' ? '/'.ltrim($path, '/') : '');

        try {
            $pending = $this->pendingClient([]);
            $clientResponse = $this->sendByMethod($pending, strtoupper($method), $url, $json);

            return $this->toHttpResponse($clientResponse);
        } catch (ConnectionException) {
            return response()->json(['error' => 'Upstream service unreachable'], 502);
        }
    }

    private function pendingClient(array $headers): PendingRequest
    {
        $timeout = (int) config('services.gateway.timeout', 15);

        return Http::timeout($timeout)
            ->withHeaders($headers)
            ->acceptJson();
    }

    /**
     * @return array<string, string>
     */
    private function forwardableRequestHeaders(Request $request): array
    {
        $out = [];
        foreach ($request->headers->all() as $name => $values) {
            if (in_array(strtolower($name), self::STRIP_REQUEST_HEADERS, true)) {
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
            default => $pending->withBody($request->getContent(), (string) $request->header('Content-Type', 'application/octet-stream'))
                ->send($method, $url),
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
}
