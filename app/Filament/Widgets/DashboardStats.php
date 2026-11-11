<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Pages\PurchaseList;
use App\Filament\Resources\Orders\OrderResource;
use App\Services\Reports\DateRange;
use App\Services\Reports\OrderReport;
use App\Services\Reports\SalesReport;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Main dashboard: today, this month and the open order pipeline at a glance.
 */
class DashboardStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $sales = app(SalesReport::class);
        $today = $sales->totals(DateRange::lastDays(1));
        $month = $sales->totals(DateRange::thisMonth());
        $last14 = $sales->series(DateRange::lastDays(14));
        $pipeline = app(OrderReport::class)->openPipeline();
        $margin = SalesReport::margin($month);

        return [
            Stat::make('আজকের বিক্রি', Money::format($today['revenue']))
                ->description("আজ অর্ডার এসেছে {$today['orders_placed']}টি · ডেলিভারি {$today['orders_delivered']}টি")
                ->chart(array_map(fn (array $row): int => (int) $row['revenue'], $last14))
                ->color('primary'),
            Stat::make('আজকের লাভ', Money::format($today['gross_profit']))
                ->color($today['gross_profit'] >= 0 ? 'success' : 'danger'),
            Stat::make('এই মাসের বিক্রি', Money::format($month['revenue']))
                ->description('লাভ '.Money::format($month['gross_profit']).($margin !== null ? " · মার্জিন {$margin}%" : ''))
                ->chart(array_map(fn (array $row): int => (int) $row['gross_profit'], $last14))
                ->color('success'),
            Stat::make('ফোনে নিশ্চিত করতে হবে', (string) ($pipeline[OrderStatus::Pending->value] ?? 0))
                ->description('অপেক্ষমান অর্ডার')
                ->color(($pipeline[OrderStatus::Pending->value] ?? 0) > 0 ? 'warning' : 'gray')
                ->url(OrderResource::getUrl('index', ['activeTab' => OrderStatus::Pending->value])),
            Stat::make('কেনাকাটা বাকি', (string) (($pipeline[OrderStatus::Confirmed->value] ?? 0) + ($pipeline[OrderStatus::Purchasing->value] ?? 0)))
                ->description('নিশ্চিত + কেনাকাটা চলছে')
                ->url(PurchaseList::getUrl()),
            Stat::make('ডেলিভারির পথে', (string) ($pipeline[OrderStatus::OutForDelivery->value] ?? 0))
                ->url(OrderResource::getUrl('index', ['activeTab' => OrderStatus::OutForDelivery->value])),
        ];
    }
}
