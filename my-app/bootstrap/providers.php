<?php

// Lists service providers Laravel loads at startup (AppServiceProvider = rate limits).

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
];
