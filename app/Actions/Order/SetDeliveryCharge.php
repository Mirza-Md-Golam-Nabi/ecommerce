<?php

namespace App\Actions\Order;

use App\Actions\Order\Concerns\EnsuresOrderIsEditable;
use App\Enums\OrderEventType;
use App\Models\Order;
use App\Models\User;
use App\Services\Pricing\OrderTotalsCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Change one order's delivery charge (default comes from store settings). Logged with from/to.
 */
final class SetDeliveryCharge
{
    use EnsuresOrderIsEditable;

    public function __construct(private OrderTotalsCalculator $totals) {}

    /**
     * @throws ValidationException
     */
    public function handle(Order $order, int $deliveryCharge, ?User $by = null, ?string $note = null): Order
    {
        $this->ensureEditable($order);

        if ($deliveryCharge < 0) {
            throw ValidationException::withMessages(['delivery_charge' => 'ডেলিভারি চার্জ ঋণাত্মক হতে পারে না।']);
        }

        return DB::transaction(function () use ($order, $deliveryCharge, $by, $note): Order {
            $from = $order->delivery_charge;
            $order->delivery_charge = $deliveryCharge;

            $order->events()->create([
                'type' => OrderEventType::DeliveryChargeChanged,
                'from' => ['delivery_charge' => $from],
                'to' => ['delivery_charge' => $deliveryCharge],
                'note' => $note,
                'user_id' => $by?->id,
            ]);

            return $this->totals->recalculate($order);
        });
    }
}
