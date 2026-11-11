<?php

namespace App\Services\Reports;

use App\Enums\CancelReason;
use App\Enums\OrderStatus;
use App\Models\Order;

/**
 * Small breakdowns read straight from `orders` (indexed, low volume): areas and cancellations.
 */
final class OrderReport
{
    /**
     * Delivered orders and revenue per area; typed "অন্যান্য" areas are listed by their text
     * so frequent ones can be added as real areas.
     *
     * @return list<array{area: string, is_other: bool, orders: int, revenue: int}>
     */
    public function areas(DateRange $range): array
    {
        return Order::query()
            ->where('status', OrderStatus::Delivered)
            ->whereBetween('delivered_at', [$range->from->startOfDay(), $range->to->endOfDay()])
            ->selectRaw('area_name, max(case when area_id is null then 1 else 0 end) as is_other, count(*) as orders, sum(subtotal) as revenue')
            ->groupBy('area_name')
            ->orderByDesc('revenue')
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'area' => (string) $row->area_name,
                'is_other' => (bool) $row->is_other,
                'orders' => (int) $row->orders,
                'revenue' => (int) $row->revenue,
            ])
            ->all();
    }

    /**
     * Cancelled orders (placed in the range) per reason.
     *
     * @return list<array{reason: string, label: string, orders: int}>
     */
    public function cancelReasons(DateRange $range): array
    {
        return Order::query()
            ->where('status', OrderStatus::Cancelled)
            ->whereBetween('placed_at', [$range->from->startOfDay(), $range->to->endOfDay()])
            ->selectRaw('cancel_reason, count(*) as orders')
            ->groupBy('cancel_reason')
            ->orderByDesc('orders')
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'reason' => (string) $row->cancel_reason,
                'label' => CancelReason::tryFrom((string) $row->cancel_reason)?->getLabel() ?? 'অজানা',
                'orders' => (int) $row->orders,
            ])
            ->all();
    }

    /**
     * Orders currently waiting at each active step (not date-bound).
     *
     * @return array<string, int> status value => count
     */
    public function openPipeline(): array
    {
        return Order::query()
            ->whereIn('status', OrderStatus::active())
            ->selectRaw('status, count(*) as orders')
            ->groupBy('status')
            ->toBase()
            ->pluck('orders', 'status')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();
    }
}
