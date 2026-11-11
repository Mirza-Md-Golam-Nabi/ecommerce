<?php

namespace App\Actions\Order;

use App\Actions\Order\Concerns\EnsuresOrderIsEditable;
use App\Enums\OrderEventType;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Pricing\OrderTotalsCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Actual purchase cost per selling unit of one order line (poisha) — drives real profit.
 */
final class SetItemCost
{
    use EnsuresOrderIsEditable;

    public function __construct(private OrderTotalsCalculator $totals) {}

    /**
     * @throws ValidationException
     */
    public function handle(OrderItem $item, int $unitCost, ?User $by = null, bool $recalculate = true): OrderItem
    {
        $order = $item->loadMissing('order')->order;
        $this->ensureEditable($order);

        if ($unitCost < 0) {
            throw ValidationException::withMessages(['unit_cost' => 'ক্রয়মূল্য ঋণাত্মক হতে পারে না।']);
        }

        return DB::transaction(function () use ($item, $order, $unitCost, $by, $recalculate): OrderItem {
            $from = $item->actual_unit_cost;

            $item->update(['actual_unit_cost' => $unitCost]);

            $order->events()->create([
                'order_item_id' => $item->id,
                'type' => OrderEventType::ItemCostSet,
                'from' => ['unit_cost' => $from],
                'to' => ['unit_cost' => $unitCost],
                'user_id' => $by?->id,
            ]);

            if ($recalculate) {
                $this->totals->recalculate($order);
            }

            return $item;
        });
    }
}
