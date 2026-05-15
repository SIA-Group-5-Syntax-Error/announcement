<?php

// All API routes require api.key + rate limiting. Prefix is /api (added by Laravel).

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ApiGatewayController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api.key', 'throttle:gateway'])->group(function (): void {
    Route::any('/gateway/{resource}/{path?}', [ApiGatewayController::class, 'proxy'])
        ->where('path', '.*');

    // Announcements use query param ?id= for single-resource operations (GET one, PUT, PATCH, DELETE).
    Route::get('/announcements', [AnnouncementController::class, 'apiAnnouncements']);
    Route::post('/announcements', [AnnouncementController::class, 'store']);
    Route::match(['put', 'patch'], '/announcements', [AnnouncementController::class, 'update']);
    Route::delete('/announcements', [AnnouncementController::class, 'destroy']);
});
