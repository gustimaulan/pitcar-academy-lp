<?php

namespace App\Filament\Analytics;

use Filament\Widgets\ChartWidget;

/** Revenue over the chosen range, split into checkout on the site (DOKU) and Scalev. */
class RevenueChart extends ChartWidget
{
    use ReadsReport;

    // Sums over the whole range; once a minute is fresh enough and spares the database.
    protected ?string $pollingInterval = '60s';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '320px';

    public function getHeading(): string
    {
        return 'Pendapatan '.$this->perUnit();
    }

    public function getDescription(): ?string
    {
        // Keyed by period start, like series().
        $revenue = array_map(fn (array $b) => $b['revenue'], $this->report()->series());
        $total = array_sum($revenue);

        if ($total === 0) {
            return 'Belum ada penjualan di rentang ini.';
        }

        $best = array_search(max($revenue), $revenue, true);

        return 'Total '.Format::rupiah($total)
            .' · rata-rata '.Format::rupiah($total / count($revenue)).' '.$this->perUnit()
            .' · tertinggi '.$this->report()->label($best).' ('.Format::rupiah($revenue[$best]).')';
    }

    protected function getData(): array
    {
        $report = $this->report();
        $buckets = $report->series();

        return [
            'datasets' => [
                [
                    'label' => 'Checkout web (DOKU)',
                    'data' => array_column($buckets, 'revenue_doku'),
                    'backgroundColor' => '#CC0000',
                    'borderRadius' => 2,
                    'stack' => 'revenue',
                ],
                [
                    'label' => 'Scalev',
                    'data' => array_column($buckets, 'revenue_scalev'),
                    'backgroundColor' => '#A8A29E',
                    'borderRadius' => 2,
                    'stack' => 'revenue',
                ],
            ],
            'labels' => array_map($report->label(...), array_keys($buckets)),
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
                'x' => ['stacked' => true],
                'y' => ['stacked' => true, 'beginAtZero' => true, 'title' => ['display' => true, 'text' => 'Pendapatan (Rp)']],
            ],
        ];
    }
}
