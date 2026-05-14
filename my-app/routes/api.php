<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;

// Example test route
Route::get('/announcement', function (Request $request) {
    $word = $request->query('word', 'example');

    return response()->json([
        'status' => 'success',
        'word' => $word,
        'definition' => 'test'
    ]);
});

// Route that fetches data from MockAPI via a controller
Route::get('/users', [Controller::class, 'index']);
