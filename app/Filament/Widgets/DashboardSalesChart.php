<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Reports\SalesChart;
use App\Services\Reports\DateRange;
use App\Services\Reports\SalesReport;
use Filament\Widgets\ChartWidget;

class DashboardSalesChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'শেষ ৩০ দিনের বিক্রি ও লাভ';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        return SalesChart::chartData(app(SalesReport::class)->series(DateRange::lastDays(30)));
    }

    protected function getType(): string
    {
        return 'line';
    }
}
