<?php

// Public web routes (no API key). Dashboard at /.

use App\Http\Controllers\AnnouncementController;

Route::get('/', [AnnouncementController::class, 'index']);
