<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class AnnouncementController extends Controller
{
    public function index()
    {
        try {

            $response = Http::get('https://69f82c35dd0c226688ee33af.mockapi.io/announcements/');

            if ($response->successful()) {

                $announcements = $response->json();

                return view('dashboard', compact('announcements'));

            } else {

                return view('dashboard', [
                    'announcements' => [],
                    'error' => 'Failed to fetch announcements.'
                ]);
            }

        } catch (\Exception $e) {

            return view('dashboard', [
                'announcements' => [],
                'error' => 'Something went wrong.'
            ]);
        }
    }
}