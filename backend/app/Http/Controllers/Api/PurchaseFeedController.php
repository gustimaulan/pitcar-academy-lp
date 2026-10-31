<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScalevPurchase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PurchaseFeedController extends Controller
{
    /** Prefix shared with SyncScalevPurchases, which clears every variant. */
    public const CACHE_KEY = 'purchases:recent-feed';

    public function index(Request $request): JsonResponse
    {
        // ?product=ac limits the feed to one class. Without it the feed stays
        // mixed, as before, so an older front end keeps working.
        $product = $request->query('product');
        $product = is_string($product) && $product !== '' ? strtolower($product) : null;

        $patterns = null;

        if ($product !== null) {
            $patterns = config("services.scalev.products.{$product}");

            // Unknown product: say nothing rather than show someone else's buyers.
            if (! is_array($patterns) || $patterns === []) {
                return response()->json(['total_purchases' => 0, 'purchases' => []]);
            }
        }

        $feed = Cache::remember(self::cacheKey($product), now()->addMinute(), function () use ($patterns) {
            $query = fn (): Builder => ScalevPurchase::query()
                ->when($patterns !== null, fn (Builder $q) => $q->where(function (Builder $q) use ($patterns) {
                    foreach ($patterns as $pattern) {
                        $q->orWhereRaw('LOWER(product_name) LIKE ?', ['%'.mb_strtolower($pattern).'%']);
                    }
                }));

            return [
                'total_purchases' => $query()->count(),
                'purchases' => $query()
                    ->orderByDesc('purchased_at')
                    ->limit(10)
                    ->get()
                    ->map(fn (ScalevPurchase $purchase) => $purchase->toPopupData())
                    ->all(),
            ];
        });

        return response()->json($feed);
    }

    public static function cacheKey(?string $product): string
    {
        return $product === null ? self::CACHE_KEY : self::CACHE_KEY.':'.$product;
    }

    /** Every cache key the feed can be stored under. */
    public static function cacheKeys(): array
    {
        return [
            self::cacheKey(null),
            ...array_map(self::cacheKey(...), array_keys((array) config('services.scalev.products', []))),
        ];
    }
}
