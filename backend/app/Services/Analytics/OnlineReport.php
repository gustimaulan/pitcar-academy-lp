<?php

namespace App\Services\Analytics;

use App\Enums\OrderStatus;
use App\Models\AdSpendDaily;
use App\Models\Order;
use App\Models\PageVisit;
use App\Models\ScalevPurchase;
use App\Support\TrafficSource;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Figures for the online-class analytics page over a range of days in the
 * display timezone. A sale is a paid DOKU order or a completed Scalev order;
 * a bundle bought in one DOKU checkout is one sale.
 */
class OnlineReport
{
    public readonly CarbonImmutable $from;

    public readonly CarbonImmutable $to;

    private ?Collection $salesCache = null;

    public function __construct(CarbonImmutable $from, CarbonImmutable $to)
    {
        $tz = config('app.display_timezone');
        $this->from = $from->setTimezone($tz)->startOfDay();
        $this->to = $to->setTimezone($tz)->endOfDay();
    }

    /**
     * The analytics page filter: a preset, "all" for everything on record, or
     * "custom" with from and until as Y-m-d dates.
     */
    public static function forFilters(?array $filters): self
    {
        $filters ??= [];
        $range = $filters['range'] ?? '30';

        if ($range !== 'custom') {
            return self::forPreset($range);
        }

        $today = CarbonImmutable::now(config('app.display_timezone'));
        $until = self::parseDate($filters['until'] ?? null) ?? $today;
        $from = self::parseDate($filters['from'] ?? null) ?? $until->subDays(29);

        return new self($from->min($until), $from->max($until));
    }

    /** "7", "30", "90", "this_month", "last_month" or "all", relative to today. */
    public static function forPreset(?string $preset): self
    {
        $today = CarbonImmutable::now(config('app.display_timezone'));

        return match ($preset) {
            '7' => new self($today->subDays(6), $today),
            '90' => new self($today->subDays(89), $today),
            'this_month' => new self($today->startOfMonth(), $today),
            'last_month' => new self($today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()),
            'all' => new self(self::firstRecord() ?? $today, $today),
            default => new self($today->subDays(29), $today),
        };
    }

    /** The day of the oldest visit, sale or ad spend, in the display timezone. */
    private static function firstRecord(): ?CarbonImmutable
    {
        $tz = config('app.display_timezone');

        return collect([
            Order::query()->where('status', OrderStatus::Paid)->min('paid_at'),
            ScalevPurchase::query()->min('purchased_at'),
        ])
            ->filter()
            // Timestamps are stored in UTC; the day they fall on is a Jakarta day.
            ->map(fn ($at) => CarbonImmutable::parse($at, 'UTC')->setTimezone($tz))
            ->concat(collect([
                PageVisit::query()->min('visit_date'),
                AdSpendDaily::query()->min('date'),
            ])->filter()->map(fn ($day) => CarbonImmutable::parse($day, $tz)))
            ->min();
    }

    private static function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d', substr($value, 0, 10), config('app.display_timezone')) ?: null;
    }

    public function summary(): array
    {
        $visits = $this->visits();
        $visitors = (int) (clone $visits)->distinct()->count('visitor_id');
        $seconds = (int) (clone $visits)->sum('duration_seconds');
        $sales = $this->sales();
        $ads = $this->ads()->selectRaw('coalesce(sum(spend),0) as spend, coalesce(sum(link_clicks),0) as clicks')->first();
        $spend = (float) $ads->spend;
        $clicks = (int) $ads->clicks;
        $revenue = (int) $sales->sum('amount');

        return [
            'visitors' => $visitors,
            'views' => (int) (clone $visits)->count(),
            'seconds_per_visitor' => $visitors ? (int) round($seconds / $visitors) : null,
            'sales' => $sales->count(),
            'revenue' => $revenue,
            'spend' => $spend,
            'link_clicks' => $clicks,
            'cpc' => $clicks ? $spend / $clicks : null,
            'cost_per_sale' => $sales->count() && $spend > 0 ? $spend / $sales->count() : null,
            'roas' => $spend > 0 ? $revenue / $spend : null,
            'conversion' => $visitors ? $sales->count() / $visitors : null,
        ];
    }

    /** @return array<string, array{visitors: int, sales: int, revenue: int, spend: float}> keyed by Y-m-d */
    public function daily(): array
    {
        return array_map(fn (array $b) => Arr::only($b, ['visitors', 'sales', 'revenue', 'spend']), $this->buckets('day'));
    }

    /**
     * How the charts group this range so they stay readable: days up to about
     * three months, weeks (from Monday) up to a year, months beyond that.
     */
    public function unit(): string
    {
        $days = (int) $this->from->diffInDays($this->to) + 1;

        return match (true) {
            $days <= 93 => 'day',
            $days <= 371 => 'week',
            default => 'month',
        };
    }

    /**
     * Totals per day, week or month (see unit()), keyed by the first day of
     * each period as Y-m-d. Visitors are counted once per period.
     *
     * @return array<string, array{visitors: int, sales: int, revenue: int, revenue_doku: int, revenue_scalev: int, spend: float}>
     */
    public function series(): array
    {
        return $this->buckets($this->unit());
    }

    /** A chart label for a series() key. */
    public function label(string $key): string
    {
        $day = CarbonImmutable::parse($key)->locale('id');
        $acrossYears = $this->from->year !== $this->to->year;

        return match ($this->unit()) {
            'month' => $day->translatedFormat('M Y'),
            default => $day->translatedFormat($acrossYears ? 'd M y' : 'd M'),
        };
    }

    private function buckets(string $unit): array
    {
        $key = fn (CarbonImmutable $day) => match ($unit) {
            'week' => $day->startOfWeek(CarbonImmutable::MONDAY)->toDateString(),
            'month' => $day->startOfMonth()->toDateString(),
            default => $day->toDateString(),
        };
        $tz = config('app.display_timezone');

        $buckets = [];
        foreach (CarbonPeriod::create($this->from->toDateString(), $this->to->toDateString()) as $day) {
            $buckets[$key(CarbonImmutable::parse($day->toDateString(), $tz))] ??= [
                'visitors' => 0, 'sales' => 0, 'revenue' => 0, 'revenue_doku' => 0, 'revenue_scalev' => 0, 'spend' => 0.0,
            ];
        }

        // Distinct visitors per period, so a visitor back on three days of a week is one visitor that week.
        $period = $this->periodStart('visit_date', $unit);
        $this->visits()->selectRaw("{$period} as period, count(distinct visitor_id) as n")->groupByRaw($period)->get()
            ->each(function ($row) use (&$buckets) {
                $k = CarbonImmutable::parse($row->period)->toDateString();
                if (isset($buckets[$k])) {
                    $buckets[$k]['visitors'] = (int) $row->n;
                }
            });

        foreach ($this->sales() as $sale) {
            $k = $key($sale['at']);
            if (isset($buckets[$k])) {
                $buckets[$k]['sales']++;
                $buckets[$k]['revenue'] += $sale['amount'];
                $buckets[$k]['revenue_'.$sale['via']] += $sale['amount'];
            }
        }

        $this->ads()->selectRaw('date, sum(spend) as spend')->groupBy('date')->get()
            ->each(function ($row) use (&$buckets, $key, $tz) {
                $k = $key(CarbonImmutable::parse($row->date, $tz));
                if (isset($buckets[$k])) {
                    $buckets[$k]['spend'] += (float) $row->spend;
                }
            });

        return $buckets;
    }

    /** SQL for the first day of the week (Monday) or month a date column falls in. */
    private function periodStart(string $column, string $unit): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($unit) {
            'week' => match ($driver) {
                'sqlite' => "date({$column}, '-' || ((cast(strftime('%w', {$column}) as integer) + 6) % 7) || ' days')",
                'pgsql' => "cast(date_trunc('week', {$column}) as date)",
                default => "date_sub({$column}, interval weekday({$column}) day)",
            },
            'month' => match ($driver) {
                'sqlite' => "strftime('%Y-%m-01', {$column})",
                'pgsql' => "cast(date_trunc('month', {$column}) as date)",
                default => "date_format({$column}, '%Y-%m-01')",
            },
            default => $column,
        };
    }

    /** The most visited pages, with time on page per visitor. */
    public function pages(int $limit = 10): array
    {
        return $this->visits()
            ->selectRaw('path, count(distinct visitor_id) as visitors, count(*) as views, sum(duration_seconds) as seconds')
            ->groupBy('path')
            ->orderByDesc('visitors')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'key' => $r->path,
                'path' => $r->path,
                'visitors' => (int) $r->visitors,
                'views' => (int) $r->views,
                'seconds_per_visitor' => $r->visitors ? (int) round($r->seconds / $r->visitors) : 0,
            ])
            ->all();
    }

    /** Visitors and DOKU sales per source. Scalev orders carry no source. */
    public function sources(): array
    {
        $visitors = $this->visits()
            ->selectRaw('source, count(distinct visitor_id) as visitors')
            ->groupBy('source')
            ->pluck('visitors', 'source');

        $ownHost = TrafficSource::host(config('lms.frontend_url'));
        $sales = $this->dokuOrders()->get(['attribution'])
            ->countBy(fn (Order $o) => TrafficSource::classify((array) $o->attribution, $ownHost));

        return $visitors->keys()->merge($sales->keys())->unique()
            ->map(fn ($source) => [
                'key' => $source,
                'source' => TrafficSource::label($source),
                'visitors' => (int) ($visitors[$source] ?? 0),
                'sales' => (int) ($sales[$source] ?? 0),
                'conversion' => ($visitors[$source] ?? 0) ? ($sales[$source] ?? 0) / $visitors[$source] : null,
            ])
            ->sortByDesc('visitors')
            ->values()
            ->all();
    }

    public function campaigns(): array
    {
        return $this->ads()
            ->selectRaw('campaign_id, max(campaign_name) as name, sum(spend) as spend, sum(link_clicks) as clicks, sum(purchases) as purchases, max(currency) as currency')
            ->groupBy('campaign_id')
            ->orderByDesc('spend')
            ->get()
            ->map(fn ($r) => [
                'key' => $r->campaign_id,
                'name' => $r->name,
                'spend' => (float) $r->spend,
                'clicks' => (int) $r->clicks,
                'cpc' => $r->clicks ? $r->spend / $r->clicks : null,
                'purchases' => (int) $r->purchases,
                'cost_per_purchase' => $r->purchases ? $r->spend / $r->purchases : null,
                'currency' => $r->currency,
            ])
            ->all();
    }

    public function hasVisits(): bool
    {
        return PageVisit::query()->exists();
    }

    private function visits()
    {
        return PageVisit::query()->whereBetween('visit_date', [$this->from->toDateString(), $this->to->toDateString()]);
    }

    private function ads()
    {
        return AdSpendDaily::query()->whereBetween('date', [$this->from->toDateString(), $this->to->toDateString()]);
    }

    private function dokuOrders()
    {
        return Order::query()
            ->where('status', OrderStatus::Paid)
            ->whereBetween('paid_at', [$this->from->utc(), $this->to->utc()]);
    }

    /** @return Collection<int, array{at: CarbonImmutable, amount: int, via: string}> in the display timezone */
    private function sales(): Collection
    {
        $tz = config('app.display_timezone');

        return $this->salesCache ??= $this->dokuOrders()->get(['paid_at', 'amount'])
            ->map(fn (Order $o) => ['at' => CarbonImmutable::parse($o->paid_at)->setTimezone($tz), 'amount' => (int) $o->amount, 'via' => 'doku'])
            ->concat(ScalevPurchase::query()
                ->whereBetween('purchased_at', [$this->from->utc(), $this->to->utc()])
                ->get(['purchased_at', 'amount'])
                ->map(fn (ScalevPurchase $p) => ['at' => CarbonImmutable::parse($p->purchased_at)->setTimezone($tz), 'amount' => (int) $p->amount, 'via' => 'scalev']))
            ->values();
    }
}
