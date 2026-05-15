<?php

// Smoke test: home page / returns 200 (dashboard).

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        Http::fake([
            'example.test/*' => Http::response([], 200),
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
