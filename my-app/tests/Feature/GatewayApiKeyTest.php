<?php

// Tests API key auth: missing key, wrong key, Bearer token, unknown gateway resource.

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GatewayApiKeyTest extends TestCase
{
    public function test_api_returns_401_without_api_key(): void
    {
        $response = $this->getJson('/api/announcements');

        $response->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    public function test_api_returns_401_with_invalid_api_key(): void
    {
        $response = $this->getJson('/api/announcements', [
            'X-API-Key' => 'wrong-key',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    public function test_api_accepts_bearer_token(): void
    {
        Http::fake([
            'example.test/*' => Http::response([['id' => '1', 'title' => 'Hello']], 200),
        ]);

        $response = $this->getJson('/api/announcements', [
            'Authorization' => 'Bearer test-gateway-key',
        ]);

        $response->assertOk();
    }

    public function test_api_returns_404_for_unknown_gateway_resource(): void
    {
        $response = $this->getJson('/api/gateway/unknown-resource', [
            'X-API-Key' => 'test-gateway-key',
        ]);

        $response->assertNotFound()
            ->assertJsonPath('error.code', 'resource_not_found');
    }
}
