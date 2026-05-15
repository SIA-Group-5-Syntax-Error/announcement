<?php

// Tests announcements API with ?id= (list, show, create, update, delete) using fake HTTP.

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GatewayAnnouncementTest extends TestCase
{
    private function withApiKey(): array
    {
        return ['X-API-Key' => 'test-gateway-key'];
    }

    public function test_can_list_announcements_without_id_query(): void
    {
        Http::fake([
            'example.test/*' => Http::response([
                ['id' => '1', 'title' => 'One', 'description' => 'A', 'date' => 1],
            ], 200),
        ]);

        $response = $this->getJson('/api/announcements', $this->withApiKey());

        $response->assertOk()
            ->assertJsonFragment(['title' => 'One']);
    }

    public function test_can_show_single_announcement_by_query_id(): void
    {
        Http::fake([
            'example.test/announcements/2' => Http::response([
                'id' => '2',
                'title' => 'Library Extended Hours',
            ], 200),
        ]);

        $response = $this->getJson('/api/announcements?id=2', $this->withApiKey());

        $response->assertOk()
            ->assertJsonPath('id', '2')
            ->assertJsonMissingPath('0');
    }

    public function test_update_requires_id_query_parameter(): void
    {
        $response = $this->putJson('/api/announcements', [
            'title' => 'Updated',
        ], $this->withApiKey());

        $response->assertUnprocessable()
            ->assertJsonPath('error.code', 'missing_id');
    }

    public function test_can_update_announcement_with_query_id(): void
    {
        Http::fake([
            'example.test/announcements/2' => Http::response([
                'id' => '2',
                'title' => 'Updated title',
            ], 200),
        ]);

        $response = $this->putJson('/api/announcements?id=2', [
            'title' => 'Updated title',
        ], $this->withApiKey());

        $response->assertOk()
            ->assertJsonPath('title', 'Updated title');

        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && str_ends_with($request->url(), '/announcements/2'));
    }

    public function test_delete_requires_id_query_parameter(): void
    {
        $response = $this->deleteJson('/api/announcements', [], $this->withApiKey());

        $response->assertUnprocessable()
            ->assertJsonPath('error.code', 'missing_id');
    }

    public function test_can_delete_announcement_with_query_id(): void
    {
        Http::fake([
            'example.test/announcements/2' => Http::response('', 200),
        ]);

        $response = $this->deleteJson('/api/announcements?id=2', [], $this->withApiKey());

        $response->assertOk();

        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && str_ends_with($request->url(), '/announcements/2'));
    }

    public function test_store_validates_required_title(): void
    {
        $response = $this->postJson('/api/announcements', [], $this->withApiKey());

        $response->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['fields' => ['title']]]);
    }

    public function test_can_create_announcement(): void
    {
        Http::fake([
            'example.test/*' => Http::response([
                'id' => '99',
                'title' => 'New',
                'description' => 'Body',
                'date' => '2026-05-20',
            ], 201),
        ]);

        $response = $this->postJson('/api/announcements', [
            'title' => 'New',
            'description' => 'Body',
            'date' => '2026-05-20',
        ], $this->withApiKey());

        $response->assertCreated()
            ->assertJsonFragment(['title' => 'New']);
    }
}
