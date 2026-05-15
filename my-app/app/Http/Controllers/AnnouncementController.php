<?php

// Announcements: web dashboard + REST API. API uses ?id= for one item; forwards to MockAPI via RemoteGateway.

namespace App\Http\Controllers;

use App\Services\Gateway\RemoteGateway;
use App\Support\Gateway\ApiError;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AnnouncementController extends Controller
{
    public function __construct(
        private RemoteGateway $gateway,
    ) {}

    // Web page at / — loads all announcements for the Blade dashboard (no API key).
    public function index(): View
    {
        $upstream = $this->gateway->resource('announcements', 'GET', '');

        if ($upstream->getStatusCode() >= 400) {
            return view('dashboard', [
                'announcements' => [],
                'error' => 'Could not load announcements.',
            ]);
        }

        $data = json_decode($upstream->getContent(), true);
        if (! is_array($data)) {
            return view('dashboard', [
                'announcements' => [],
                'error' => 'Invalid announcements response.',
            ]);
        }

        $rows = array_is_list($data) ? $data : [$data];
        $announcements = array_map(static function (array $row): array {
            $ts = $row['date'] ?? null;

            return [
                'title' => $row['title'] ?? '',
                'date' => is_numeric($ts) ? date('Y-m-d H:i', (int) $ts) : (string) $ts,
                'content' => $row['content'] ?? $row['description'] ?? '',
            ];
        }, $rows);

        return view('dashboard', ['announcements' => $announcements]);
    }

    // GET /api/announcements or GET /api/announcements?id=2
    public function apiAnnouncements(Request $request): Response
    {
        if ($request->filled('id')) {
            return $this->showById((string) $request->query('id'));
        }

        return $this->gateway->resource('announcements', 'GET', '');
    }

    // POST /api/announcements — create (JSON body).
    public function store(Request $request): Response
    {
        return $this->gateway->resource('announcements', 'POST', '', $this->validatedPayload($request));
    }

    // PUT/PATCH /api/announcements?id=2 — update (JSON body).
    public function update(Request $request): Response
    {
        $id = $this->resolveQueryId($request);
        if ($id instanceof Response) {
            return $id;
        }

        $verb = $request->isMethod('patch') ? 'PATCH' : 'PUT';

        return $this->gateway->resource(
            'announcements',
            $verb,
            $id,
            $this->validatedPayload($request, forUpdate: true),
        );
    }

    // DELETE /api/announcements?id=2
    public function destroy(Request $request): Response
    {
        $id = $this->resolveQueryId($request);
        if ($id instanceof Response) {
            return $id;
        }

        return $this->gateway->resource('announcements', 'DELETE', $id);
    }

    // Fetches one announcement from upstream by id.
    private function showById(string $id): Response
    {
        $response = $this->gateway->resource('announcements', 'GET', $id);

        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        return $this->normalizeSingleAnnouncementResponse($response, $id);
    }

    // Reads and validates ?id= from the query string.
    private function resolveQueryId(Request $request): string|Response
    {
        if (! $request->filled('id')) {
            return ApiError::json(
                'missing_id',
                'Query parameter "id" is required (e.g. /api/announcements?id=2).',
                422,
            );
        }

        $id = $request->query('id');
        if (! is_scalar($id) || trim((string) $id) === '') {
            return ApiError::json('invalid_id', 'Query parameter "id" must be a non-empty value.', 422);
        }

        return (string) $id;
    }

    // If upstream returns a list, pick the row matching ?id=.
    private function normalizeSingleAnnouncementResponse(Response $response, string $id): Response
    {
        $data = json_decode($response->getContent(), true);

        if (! is_array($data)) {
            return $response;
        }

        if (isset($data['id']) && ! array_is_list($data)) {
            return $response;
        }

        if (! array_is_list($data)) {
            return $response;
        }

        foreach ($data as $item) {
            if (is_array($item) && (string) ($item['id'] ?? '') === (string) $id) {
                return response()->json($item, $response->getStatusCode());
            }
        }

        return ApiError::json('not_found', 'Announcement not found', 404);
    }

    // Validates title, description, date from the request body.
    private function validatedPayload(Request $request, bool $forUpdate = false): array
    {
        if ($forUpdate) {
            return $request->validate([
                'title' => ['sometimes', 'required', 'string', 'max:255'],
                'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
                'date' => ['sometimes', 'nullable'],
            ]);
        }

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'date' => ['nullable'],
        ]);
    }
}
