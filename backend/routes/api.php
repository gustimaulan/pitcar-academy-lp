<?php

use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\PurchaseFeedController;
use Illuminate\Support\Facades\Route;

Route::post('/leads', [LeadController::class, 'store'])
    ->middleware('throttle:leads')
    ->name('api.leads.store');

// Public social-proof feed for the sales popup. Masked data only; the
// throttle keeps scrapers from hammering it.
Route::get('/purchases/recent', [PurchaseFeedController::class, 'index'])
    ->middleware('throttle:30,1')
    ->name('api.purchases.recent');
