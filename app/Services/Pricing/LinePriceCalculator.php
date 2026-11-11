<?php

namespace App\Services\Pricing;

use App\Enums\OrderItemStatus;
use App\Models\OrderItem;

/**
 * Line amounts for an order item (docs/03-order-flow.md "Line pricing formula"). All values poisha.
 *
 *   fixed:       unit × (fulfilled_quantity ?? quantity)
 *   weighted:    round(unit × grams / nominal_grams), grams = actual weight ?? quantity × nominal
 *   unavailable: 0
 */
final class LinePriceCalculator
{
    public function lineTotal(OrderItem $item): int
    {
        return $this->amount($item, $item->unit_price);
    }

    /**
     * Purchase cost of the line: actual unit cost when entered, otherwise the estimate.
     */
    public function lineCost(OrderItem $item): int
    {
        return $this->amount($item, $item->actual_unit_cost ?? $item->estimated_unit_cost);
    }

    /**
     * Value of what was ordered but could not be delivered (lost sales).
     */
    public function missingValue(OrderItem $item): int
    {
        return match ($item->status) {
            OrderItemStatus::Unavailable => $item->unit_price * $item->quantity,
            OrderItemStatus::Partial => $item->unit_price * max(0, $item->quantity - (int) $item->fulfilled_quantity),
            default => 0,
        };
    }

    private function amount(OrderItem $item, int $unitAmount): int
    {
        if ($item->status === OrderItemStatus::Unavailable) {
            return 0;
        }

        if ($item->isWeighted() && $item->nominal_weight_grams) {
            $grams = $item->actual_weight_grams ?? $item->orderedGrams();

            return (int) round($unitAmount * $grams / $item->nominal_weight_grams);
        }

        return $unitAmount * ($item->fulfilled_quantity ?? $item->quantity);
    }
}
