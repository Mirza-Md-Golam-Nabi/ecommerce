<?php

namespace App\Services\Reports;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Pricing\LinePriceCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Recomputes the derived report tables for one day from raw orders (idempotent).
 * Metric definitions: docs/04-reporting.md. Placement metrics use `placed_at`;
 * revenue, cost and profit use `delivered_at` (COD: money exists only after delivery).
 */
final class DailySummaryBuilder
{
    public function __construct(private LinePriceCalculator $lines) {}

    public function rebuild(CarbonImmutable $date): void
    {
        $start = $date->startOfDay();
        $end = $date->endOfDay();

        $placed = Order::query()->with('items')->whereBetween('placed_at', [$start, $end])->get();
        $delivered = Order::query()->with('items')
            ->where('status', OrderStatus::Delivered)
            ->whereBetween('delivered_at', [$start, $end])
            ->get();

        $summary = [
            'orders_placed' => $placed->count(),
            'orders_cancelled' => $placed->where('status', OrderStatus::Cancelled)->count(),
            'orders_delivered' => $delivered->count(),
            'booked_sales' => (int) $placed->where('status', '!=', OrderStatus::Cancelled)->sum('grand_total'),
            'revenue' => (int) $delivered->sum('subtotal'),
            'delivery_income' => (int) $delivered->sum('delivery_charge'),
            'cogs' => (int) $delivered->sum('cost_total'),
            'estimated_cost_orders' => $delivered->where('cost_is_final', false)->count(),
            'lost_sales' => (int) $placed->sum('unavailable_total'),
            'unavailable_lines' => $placed->flatMap->items->where('status', OrderItemStatus::Unavailable)->count(),
            'items_delivered' => (int) $delivered->flatMap->items
                ->filter(fn (OrderItem $item): bool => $item->status->isBillable())
                ->sum(fn (OrderItem $item): int => $item->fulfilled_quantity ?? $item->quantity),
            'new_customers' => Customer::query()->whereBetween('created_at', [$start, $end])->count(),
        ];
        $summary['gross_profit'] = $summary['revenue'] - $summary['cogs'];

        $productRows = $this->productRows($date, $placed->flatMap->items, $delivered->flatMap->items);

        DB::transaction(function () use ($date, $summary, $productRows): void {
            $day = $date->toDateString();

            DB::table('daily_sales_summaries')->where('date', $day)->delete();
            DB::table('daily_product_sales')->where('date', $day)->delete();

            if (collect($summary)->contains(fn (int $value): bool => $value !== 0)) {
                DB::table('daily_sales_summaries')->insert(['date' => $day, ...$summary, 'updated_at' => now()]);
            }

            foreach (array_chunk($productRows, 500) as $chunk) {
                DB::table('daily_product_sales')->insert($chunk);
            }
        });
    }

    public function rebuildRange(DateRange $range): int
    {
        foreach ($range->days() as $day) {
            $this->rebuild(CarbonImmutable::parse($day));
        }

        return count($range->days());
    }

    /**
     * @param  iterable<OrderItem>  $placedItems
     * @param  iterable<OrderItem>  $deliveredItems
     * @return list<array<string, int|string>>
     */
    private function productRows(CarbonImmutable $date, iterable $placedItems, iterable $deliveredItems): array
    {
        $rows = [];

        $row = function (OrderItem $item) use (&$rows, $date): void {
            $rows[$item->product_id] ??= [
                'date' => $date->toDateString(),
                'product_id' => $item->product_id,
                'category_id' => $item->category_id,
                'quantity_sold' => 0,
                'grams_sold' => 0,
                'revenue' => 0,
                'cogs' => 0,
                'profit' => 0,
                'unavailable_count' => 0,
                'lost_value' => 0,
            ];
        };

        foreach ($deliveredItems as $item) {
            if (! $item->status->isBillable()) {
                continue;
            }

            $row($item);
            $rows[$item->product_id]['quantity_sold'] += $item->fulfilled_quantity ?? $item->quantity;
            $rows[$item->product_id]['grams_sold'] += $item->isWeighted() ? (int) $item->actual_weight_grams : 0;
            $rows[$item->product_id]['revenue'] += $item->line_total;
            $rows[$item->product_id]['cogs'] += $item->line_cost;
            $rows[$item->product_id]['profit'] += $item->line_total - $item->line_cost;
        }

        foreach ($placedItems as $item) {
            $lost = $this->lines->missingValue($item);

            if ($lost === 0) {
                continue;
            }

            $row($item);
            $rows[$item->product_id]['unavailable_count'] += $item->status === OrderItemStatus::Unavailable ? 1 : 0;
            $rows[$item->product_id]['lost_value'] += $lost;
        }

        return array_values($rows);
    }
}
