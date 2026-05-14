<?php

namespace App\Http\Controllers;

use App\Services\Gateway\RemoteGateway;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AnnouncementController extends Controller
{
    public function __construct(
        private RemoteGateway $gateway,
    ) {}

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

    public function apiAnnouncements(): Response
    {
        return $this->gateway->resource('announcements', 'GET', '');
    }

    public function show(string $id): Response
    {
        return $this->gateway->resource('announcements', 'GET', $id);
    }

    public function store(Request $request): Response
    {
        $request->validate([
            'title' => 'required',
        ]);

        return $this->gateway->resource('announcements', 'POST', '', [
            'title' => $request->title,
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $verb = $request->isMethod('patch') ? 'PATCH' : 'PUT';

        return $this->gateway->resource('announcements', $verb, $id, [
            'title' => $request->input('title'),
        ]);
    }

    public function destroy(string $id): Response
    {
        return $this->gateway->resource('announcements', 'DELETE', $id);
    }
}
