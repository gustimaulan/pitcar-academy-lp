<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pull completed Scalev orders for the sales popup. Purchase history is
// sparse, so every three hours is plenty; the command also runs manually via
// `php artisan scalev:sync-purchases`. No-ops gracefully when SCALEV_API_KEY
// is not configured.
Schedule::command('scalev:sync-purchases')->everyThreeHours();
