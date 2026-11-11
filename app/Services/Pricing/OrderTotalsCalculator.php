<?php

namespace App\Services\Pricing;

use App\Models\Order;
use App\Models\OrderItem;

/**
 * The only place that writes order/line amounts. Call after any item or delivery-charge change.
 */
final class OrderTotalsCalculator
{
    public function __construct(private LinePriceCalculator $lines) {}

    public function recalculate(Order $order): Order
    {
        $order->load('items');

        foreach ($order->items as $item) {
            $item->line_total = $this->lines->lineTotal($item);
            $item->line_cost = $this->lines->lineCost($item);

            if ($item->isDirty()) {
                $item->save();
            }
        }

        $billable = $order->items->filter(fn (OrderItem $item): bool => $item->status->isBillable());

        $order->subtotal = (int) $billable->sum('line_total');
        $order->cost_total = (int) $billable->sum('line_cost');
        $order->unavailable_total = (int) $order->items->sum(fn (OrderItem $item): int => $this->lines->missingValue($item));
        $order->items_count = $order->items->count();
        $order->cost_is_final = $billable->isNotEmpty() && $billable->every(fn (OrderItem $item): bool => $item->actual_unit_cost !== null);
        $order->grand_total = $order->subtotal + $order->delivery_charge;

        $order->save();

        return $order;
    }
}
