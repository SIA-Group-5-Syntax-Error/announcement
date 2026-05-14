<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;

class AnnouncementController extends Controller
{
    private string $apiUrl;

    public function __construct()
    {
        // Make sure this exists in your .env file
        $this->apiUrl = env('MOCKAPI_URL');
    }

    // GET all announcements
    public function apiAnnouncements(): JsonResponse
    {
         $response = Http::get($this->apiUrl);

        return response()->json([
        'raw' => $response->body(),
        'decoded' => $response->json()
    ]);
    }

    // OPTIONAL: GET single announcement
    public function show(string $id): JsonResponse
    {
        $response = Http::get($this->apiUrl . '/' . $id);

        return response()->json($response->json());
    }

    // OPTIONAL: CREATE announcement
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required',
        ]);

        $response = Http::post($this->apiUrl, [
            'title' => $request->title,
        ]);

        return response()->json([
            'message' => 'Announcement created successfully',
            'data' => $response->json()
        ]);
    }

    // OPTIONAL: UPDATE announcement
    public function update(Request $request, string $id): JsonResponse
    {
        $response = Http::put($this->apiUrl . '/' . $id, [
            'title' => $request->title,
        ]);

        return response()->json([
            'message' => 'Announcement updated successfully',
            'data' => $response->json()
        ]);
    }

    // OPTIONAL: DELETE announcement
    public function destroy(string $id): JsonResponse
    {
        Http::delete($this->apiUrl . '/' . $id);

        return response()->json([
            'message' => 'Announcement deleted successfully'
        ]);
    }
}