<?php

namespace App\Filament\Analytics;

use App\Services\Analytics\MetaAds;
use Filament\Widgets\ChartWidget;

class AdSpendChart extends ChartWidget
{
    use ReadsReport;

    // Sums over the whole range; once a minute is fresh enough and spares the database.
    protected ?string $pollingInterval = '60s';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '320px';

    public function getHeading(): string
    {
        return 'Biaya iklan Meta dan penjualan '.$this->perUnit();
    }

    public function getDescription(): ?string
    {
        return app(MetaAds::class)->configured()
            ? 'Hari dengan biaya tinggi tanpa penjualan layak dicek kampanyenya.'
            : 'Belum terhubung ke Meta. Atur di Pengaturan › Iklan Meta, lalu grafik ini terisi.';
    }

    protected function getData(): array
    {
        $report = $this->report();
        $days = $report->series();

        return [
            'datasets' => [
                [
                    'type' => 'line',
                    'label' => 'Penjualan',
                    'data' => array_column($days, 'sales'),
                    'borderColor' => '#CC0000',
                    'backgroundColor' => '#CC0000',
                    'pointRadius' => 2,
                    'tension' => 0.25,
                    'yAxisID' => 'sales',
                    'order' => 1,
                ],
                [
                    'type' => 'bar',
                    'label' => 'Biaya iklan (Rp)',
                    'data' => array_map(fn ($v) => round($v), array_column($days, 'spend')),
                    'backgroundColor' => '#A8A29E',
                    'borderRadius' => 2,
                    'yAxisID' => 'spend',
                    'order' => 2,
                ],
            ],
            'labels' => array_map($report->label(...), array_keys($days)),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            // Indonesian number format on axes and tooltips: 250.000, not 250,000.
            'locale' => 'id-ID',
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'scales' => [
                // Chart.js keeps its default axis next to the named ones unless told not to.
                'y' => ['display' => false],
                'spend' => ['position' => 'left', 'beginAtZero' => true, 'title' => ['display' => true, 'text' => 'Biaya iklan (Rp)']],
                'sales' => ['position' => 'right', 'beginAtZero' => true, 'ticks' => ['precision' => 0], 'grid' => ['drawOnChartArea' => false], 'title' => ['display' => true, 'text' => 'Penjualan']],
            ],
        ];
    }
}
