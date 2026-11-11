<?php

namespace App\Filament\Widgets\Reports;

use App\Services\Reports\DateRange;
use App\Services\Reports\SalesReport;
use App\Support\Money;
use App\Support\Theme;
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
        $colors = Theme::current()->colors;

        $dataset = fn (string $label, string $metric, string $color): array => [
            'label' => $label,
            'data' => array_map(fn (array $row): float => Money::toTaka((int) $row[$metric]), $series),
            'borderColor' => $color,
            'backgroundColor' => $color.'26',
            'fill' => true,
            'tension' => 0.3,
        ];

        return [
            'datasets' => [
                $dataset('বিক্রি (৳)', 'revenue', $colors['primary']),
                $dataset('লাভ (৳)', 'gross_profit', $colors['accent']),
            ],
            'labels' => array_map(fn (array $row): string => (string) $row['label'], $series),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
