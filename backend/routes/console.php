<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pull paid Scalev orders for the sales popup. No-ops gracefully when
// SCALEV_API_KEY is not configured.
Schedule::command('scalev:sync-purchases')->everyFiveMinutes();
