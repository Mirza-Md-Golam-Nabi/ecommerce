<?php

namespace App\Filament\Widgets\Reports;

use App\Services\Reports\DateRange;
use App\Services\Reports\SalesReport;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Revenue vs profit over the selected range, grouped by day/week/month (page filter).
 */
class SalesChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'বিক্রি ও লাভ';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '320px';

    protected function getData(): array
    {
        $series = app(SalesReport::class)->series(
            DateRange::fromFilters($this->pageFilters),
            (string) ($this->pageFilters['group_by'] ?? 'day'),
        );

        return self::chartData($series);
    }

    /**
     * @param  list<array<string, int|string>>  $series
     * @return array<string, mixed>
     */
    public static function chartData(array $series): array
    {
        $taka = fn (string $metric): array => array_map(fn (array $row): float => ((int) $row[$metric]) / 100, $series);

        return [
            'datasets' => [
                [
                    'label' => 'বিক্রি (৳)',
                    'data' => $taka('revenue'),
                    'borderColor' => '#0F3D2E',
                    'backgroundColor' => 'rgba(15, 61, 46, 0.12)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'লাভ (৳)',
                    'data' => $taka('gross_profit'),
                    'borderColor' => '#C9A227',
                    'backgroundColor' => 'rgba(201, 162, 39, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => array_map(fn (array $row): string => (string) $row['label'], $series),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
