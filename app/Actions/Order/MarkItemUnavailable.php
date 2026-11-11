<?php

namespace App\Actions\Order;

use App\Actions\Order\Concerns\EnsuresOrderIsEditable;
use App\Enums\CancelReason;
use App\Enums\OrderEventType;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Pricing\OrderTotalsCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Item not found in the market: keep the row, bill nothing, show the customer a polite message
 * (docs/03-order-flow.md "Unavailable items"). If nothing billable remains, the order is cancelled.
 */
final class MarkItemUnavailable
{
    use EnsuresOrderIsEditable;

    public function __construct(
        private OrderTotalsCalculator $totals,
        private ChangeOrderStatus $changeStatus,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(OrderItem $item, ?string $note = null, ?User $by = null): OrderItem
    {
        $order = $item->loadMissing('order')->order;
        $this->ensureEditable($order);

        return DB::transaction(function () use ($item, $order, $note, $by): OrderItem {
            $from = $item->status;

            $item->update([
                'status' => OrderItemStatus::Unavailable,
                'fulfilled_quantity' => null,
                'actual_weight_grams' => null,
                'unavailable_note' => $note,
            ]);

            $order->events()->create([
                'order_item_id' => $item->id,
                'type' => OrderEventType::ItemUnavailable,
                'from' => ['status' => $from->value],
                'to' => ['status' => OrderItemStatus::Unavailable->value],
                'note' => $note,
                'user_id' => $by?->id,
            ]);

            $this->totals->recalculate($order);

            $hasBillableItems = $order->items->contains(fn (OrderItem $line): bool => $line->status->isBillable());

            if (! $hasBillableItems && $order->status->canTransitionTo(OrderStatus::Cancelled)) {
                $this->changeStatus->handle($order, OrderStatus::Cancelled, $by, 'কোনো পণ্য পাওয়া যায়নি', CancelReason::AllUnavailable);
            }

            return $item;
        });
    }
}
