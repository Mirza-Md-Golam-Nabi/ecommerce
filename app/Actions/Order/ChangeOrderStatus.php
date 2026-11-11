<?php

namespace App\Actions\Order;

use App\Enums\CancelReason;
use App\Enums\OrderEventType;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only way to change an order's status (docs/03-order-flow.md "Order status machine").
 * Sets the matching `*_at` timestamp, writes an order event and keeps customer/product counters.
 */
final class ChangeOrderStatus
{
    /**
     * @param  User|null  $by  Admin making the change; null = customer or system.
     *
     * @throws ValidationException
     */
    public function handle(
        Order $order,
        OrderStatus $to,
        ?User $by = null,
        ?string $note = null,
        ?CancelReason $cancelReason = null,
        ?int $collectedAmount = null,
    ): Order {
        $from = $order->status;

        if (! $from->canTransitionTo($to)) {
            throw ValidationException::withMessages([
                'status' => "\"{$from->getLabel()}\" অবস্থা থেকে \"{$to->getLabel()}\" করা যায় না।",
            ]);
        }

        if ($to === OrderStatus::Cancelled && $cancelReason === null) {
            throw ValidationException::withMessages(['cancel_reason' => 'বাতিলের কারণ দিন।']);
        }

        if ($to === OrderStatus::Delivered) {
            $this->guardDeliverable($order);
        }

        return DB::transaction(function () use ($order, $from, $to, $by, $note, $cancelReason, $collectedAmount): Order {
            $order->status = $to;
            $order->{$to->timestampColumn()} = now();

            if ($to === OrderStatus::Cancelled) {
                $order->cancel_reason = $cancelReason;
                $order->customer()->increment('cancelled_count');
            }

            if ($to === OrderStatus::Delivered) {
                $order->payment_status = PaymentStatus::Paid;
                $order->collected_amount = $collectedAmount ?? $order->grand_total;
                $order->customer()->increment('delivered_count');
                $this->addSoldQuantities($order);
            }

            $order->save();

            $order->events()->create([
                'type' => OrderEventType::StatusChanged,
                'from' => ['status' => $from->value],
                'to' => array_filter(['status' => $to->value, 'cancel_reason' => $cancelReason?->value]),
                'note' => $note,
                'user_id' => $by?->id,
            ]);

            return $order;
        });
    }

    /**
     * @throws ValidationException
     */
    private function guardDeliverable(Order $order): void
    {
        $unweighed = $order->items()
            ->where('sale_type', 'weighted')
            ->where('status', '!=', OrderItemStatus::Unavailable)
            ->whereNull('actual_weight_grams')
            ->pluck('product_name');

        if ($unweighed->isNotEmpty()) {
            throw ValidationException::withMessages([
                'status' => 'ডেলিভারির আগে এই পণ্যগুলোর আসল ওজন দিন: '.$unweighed->implode(', '),
            ]);
        }
    }

    private function addSoldQuantities(Order $order): void
    {
        $order->items()
            ->where('status', '!=', OrderItemStatus::Unavailable)
            ->get()
            ->each(fn (OrderItem $item): int => Product::withTrashed()
                ->whereKey($item->product_id)
                ->increment('total_sold', $item->fulfilled_quantity ?? $item->quantity));
    }
}
