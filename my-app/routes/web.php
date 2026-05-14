<?php

use App\Http\Controllers\AnnouncementController;

Route::get('/', [AnnouncementController::class, 'index']);
