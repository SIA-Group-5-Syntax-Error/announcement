<?php

// Entry point for /api/gateway/{resource}/{path?} — generic proxy to any configured upstream.

namespace App\Http\Controllers;

use App\Services\Gateway\RemoteGateway;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiGatewayController extends Controller
{
    public function __construct(
        private RemoteGateway $gateway,
    ) {}

    public function proxy(Request $request, string $resource, ?string $path = null): Response
    {
        return $this->gateway->proxy($request, $resource, $path);
    }
}
