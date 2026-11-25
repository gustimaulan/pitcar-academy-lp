<?php

namespace App\Filament\Analytics;

use App\Services\Analytics\OnlineReport;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/** Every widget on the analytics page reads the same range from the page filter. */
trait ReadsReport
{
    use InteractsWithPageFilters;

    private ?OnlineReport $report = null;

    private ?string $reportKey = null;

    protected function report(): OnlineReport
    {
        $filters = [
            'range' => $this->pageFilters['range'] ?? '30',
            'from' => $this->pageFilters['from'] ?? null,
            'until' => $this->pageFilters['until'] ?? null,
        ];
        $key = implode('|', $filters);

        if ($this->report === null || $this->reportKey !== $key) {
            $this->report = OnlineReport::forFilters($filters);
            $this->reportKey = $key;
        }

        return $this->report;
    }

    /** "per hari", "per minggu" or "per bulan", matching how the charts group the range. */
    protected function perUnit(): string
    {
        return match ($this->report()->unit()) {
            'week' => 'per minggu',
            'month' => 'per bulan',
            default => 'per hari',
        };
    }
}
