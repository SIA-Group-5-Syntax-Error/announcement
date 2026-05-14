<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ApiGatewayController;
use Illuminate\Support\Facades\Route;

Route::any('/gateway/{resource}/{path?}', [ApiGatewayController::class, 'proxy'])
    ->where('path', '.*');

Route::get('/announcements', [AnnouncementController::class, 'apiAnnouncements']);
Route::get('/announcements/{id}', [AnnouncementController::class, 'show']);
Route::post('/announcements', [AnnouncementController::class, 'store']);
Route::match(['put', 'patch'], '/announcements/{id}', [AnnouncementController::class, 'update']);
Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy']);