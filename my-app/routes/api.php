<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnnouncementController;

Route::get('/announcements', [AnnouncementController::class, 'apiAnnouncements']);