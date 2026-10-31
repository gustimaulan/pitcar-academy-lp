<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScalevPurchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PurchaseFeedController extends Controller
{
    private const CACHE_KEY = 'purchases:recent-feed';

    public function index(): JsonResponse
    {
        $feed = Cache::remember(self::CACHE_KEY, now()->addMinute(), function () {
            $items = ScalevPurchase::query()
                ->orderByDesc('purchased_at')
                ->limit(10)
                ->get()
                ->map(fn (ScalevPurchase $purchase) => $purchase->toPopupData())
                ->all();

            return [
                'total_purchases' => ScalevPurchase::count(),
                'purchases' => $items,
            ];
        });

        return response()->json($feed);
    }
}
