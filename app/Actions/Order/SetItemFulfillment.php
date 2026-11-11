<?php

namespace App\Actions\Order;

use App\Actions\Order\Concerns\EnsuresOrderIsEditable;
use App\Enums\OrderEventType;
use App\Enums\OrderItemStatus;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Pricing\OrderTotalsCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Record what was actually bought: the weighed grams (weighted item) or the bought quantity
 * (fixed item; less than ordered = partial). Also restores an item previously marked unavailable.
 */
final class SetItemFulfillment
{
    use EnsuresOrderIsEditable;

    public function __construct(private OrderTotalsCalculator $totals) {}

    /**
     * @param  int  $amount  Grams for weighted items, units for fixed items.
     *
     * @throws ValidationException
     */
    public function handle(OrderItem $item, int $amount, ?User $by = null): OrderItem
    {
        $order = $item->loadMissing('order')->order;
        $this->ensureEditable($order);

        if ($item->isWeighted()) {
            $this->guardWeight($item, $amount);
        } elseif ($amount < 1 || $amount > $item->quantity) {
            throw ValidationException::withMessages(['amount' => 'পরিমাণ ১ থেকে অর্ডারের পরিমাণের মধ্যে হতে হবে।']);
        }

        return DB::transaction(function () use ($item, $order, $amount, $by): OrderItem {
            $from = ['status' => $item->status->value, 'amount' => $item->isWeighted() ? $item->actual_weight_grams : $item->fulfilled_quantity];

            if ($item->isWeighted()) {
                $item->fill(['actual_weight_grams' => $amount, 'fulfilled_quantity' => null, 'status' => OrderItemStatus::Fulfilled]);
            } else {
                $item->fill([
                    'fulfilled_quantity' => $amount,
                    'status' => $amount === $item->quantity ? OrderItemStatus::Fulfilled : OrderItemStatus::Partial,
                ]);
            }

            $item->unavailable_note = null;
            $item->save();

            $order->events()->create([
                'order_item_id' => $item->id,
                'type' => $item->isWeighted() ? OrderEventType::ItemWeightSet : OrderEventType::ItemQuantityChanged,
                'from' => $from,
                'to' => ['status' => $item->status->value, 'amount' => $amount],
                'user_id' => $by?->id,
            ]);

            $this->totals->recalculate($order);

            return $item;
        });
    }

    /**
     * @throws ValidationException
     */
    private function guardWeight(OrderItem $item, int $grams): void
    {
        $ordered = $item->quantity * (int) $item->nominal_weight_grams;

        if ($grams < 1 || $grams > $ordered * 2) {
            throw ValidationException::withMessages(['amount' => 'ওজন সঠিক নয় — অর্ডারের ওজনের দ্বিগুণের বেশি হতে পারে না।']);
        }
    }
}
