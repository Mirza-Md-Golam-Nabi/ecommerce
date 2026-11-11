<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Support\ProfitColor;
use App\Services\Reports\DateRange;
use App\Services\Reports\SalesReport;
use App\Support\Money;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Headline numbers for the selected date range.
 */
class ReportStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $totals = app(SalesReport::class)->totals(DateRange::fromFilters($this->pageFilters));
        $estimated = $totals['estimated_cost_orders'] > 0 ? " · {$totals['estimated_cost_orders']}টি অর্ডারের ক্রয়মূল্য আনুমানিক" : '';
        $cancelRate = $totals['orders_placed'] > 0 ? round($totals['orders_cancelled'] * 100 / $totals['orders_placed'], 1) : 0;
        $averageOrder = $totals['orders_delivered'] > 0 ? intdiv($totals['revenue'], $totals['orders_delivered']) : 0;

        return [
            Stat::make('বিক্রি (ডেলিভারি হওয়া)', Money::format($totals['revenue']))
                ->description('ডেলিভারি চার্জ আলাদা: '.Money::format($totals['delivery_income']))
                ->color('primary'),
            Stat::make('লাভ', Money::format($totals['gross_profit']))
                ->description((SalesReport::marginLabel($totals) ?? 'এখনো বিক্রি নেই').$estimated)
                ->color(ProfitColor::for($totals['gross_profit'])),
            Stat::make('অর্ডার', "{$totals['orders_delivered']} / {$totals['orders_placed']}")
                ->description("ডেলিভারি / মোট অর্ডার · বাতিল {$totals['orders_cancelled']} ({$cancelRate}%)"),
            Stat::make('গড় অর্ডার', Money::format($averageOrder))
                ->description("{$totals['items_delivered']}টি পণ্য ডেলিভারি · নতুন কাস্টমার {$totals['new_customers']}"),
            Stat::make('হারানো বিক্রি', Money::format($totals['lost_sales']))
                ->description("বাজারে পাওয়া যায়নি: {$totals['unavailable_lines']}টি পণ্য-লাইন")
                ->color($totals['lost_sales'] > 0 ? 'warning' : 'gray'),
            Stat::make('ক্রয়মূল্য', Money::format($totals['cogs']))
                ->description('ডেলিভারি হওয়া পণ্যের মোট ক্রয়মূল্য'),
        ];
    }
}
