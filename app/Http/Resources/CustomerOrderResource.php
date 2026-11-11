<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Order as the customer sees it ("আমার অর্ডার"). Expects `items` loaded.
 *
 * @mixin Order
 */
class CustomerOrderResource extends JsonResource
{
    /**
     * Steps shown on the customer's order timeline.
     *
     * @var list<OrderStatus>
     */
    private const TIMELINE = [
        OrderStatus::Pending,
        OrderStatus::Confirmed,
        OrderStatus::Purchasing,
        OrderStatus::OutForDelivery,
        OrderStatus::Delivered,
    ];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'order_number' => $this->order_number,
            'status' => ['value' => $this->status->value, 'label' => $this->status->getLabel()],
            'cancel_reason' => $this->cancel_reason?->getLabel(),
            'can_cancel' => $this->status->isCancellableByCustomer(),
            'placed_at' => $this->placed_at->toIso8601String(),
            'timeline' => array_map(fn (OrderStatus $step): array => [
                'status' => $step->value,
                'label' => $step->getLabel(),
                'at' => $this->{$step->timestampColumn()}?->toIso8601String(),
            ], self::TIMELINE),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'items' => $this->items->map(fn (OrderItem $item): array => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'unit_label' => $item->unit_label,
                'quantity' => $item->quantity,
                'fulfilled_quantity' => $item->fulfilled_quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
                'is_weighted' => $item->isWeighted(),
                'actual_weight_grams' => $item->actual_weight_grams,
                'status' => $item->status->value,
                'message' => $item->customerMessage(),
            ])->all(),
            'subtotal' => $this->subtotal,
            'delivery_charge' => $this->delivery_charge,
            'grand_total' => $this->grand_total,
            'unavailable_total' => $this->unavailable_total,
            'is_estimated' => $this->isTotalEstimated(),
            'shipping' => [
                'name' => $this->shipping_name,
                'phone' => $this->shipping_phone,
                'area' => $this->area_name,
                'address_line' => $this->address_line,
            ],
            'customer_note' => $this->customer_note,
        ];
    }
}
