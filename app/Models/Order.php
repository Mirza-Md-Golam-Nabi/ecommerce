<?php

namespace App\Models;

use App\Enums\CancelReason;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * COD order. Totals are maintained only by App\Services\Pricing\OrderTotalsCalculator and
 * status only by order Actions — never update them directly (docs/07-conventions.md).
 *
 * @property int $id
 * @property string $order_number
 * @property int $customer_id
 * @property OrderStatus $status
 * @property CancelReason|null $cancel_reason
 * @property PaymentStatus $payment_status
 * @property int $subtotal
 * @property int $delivery_charge
 * @property int $grand_total
 * @property int|null $collected_amount
 * @property int $cost_total
 * @property bool $cost_is_final
 * @property int $unavailable_total
 * @property int $items_count
 * @property string $shipping_name
 * @property string $shipping_phone
 * @property string $area_name
 * @property string $address_line
 * @property Carbon $placed_at
 */
#[Fillable([
    'order_number', 'customer_id', 'status', 'cancel_reason', 'payment_method', 'payment_status',
    'subtotal', 'delivery_charge', 'grand_total', 'collected_amount', 'cost_total', 'cost_is_final',
    'unavailable_total', 'items_count', 'shipping_name', 'shipping_phone', 'area_id', 'area_name',
    'address_line', 'customer_note', 'admin_note', 'placed_at', 'confirmed_at', 'purchasing_at',
    'out_for_delivery_at', 'delivered_at', 'cancelled_at', 'ip_address',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',
            'status' => OrderStatus::class,
            'cancel_reason' => CancelReason::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => 'integer',
            'delivery_charge' => 'integer',
            'grand_total' => 'integer',
            'collected_amount' => 'integer',
            'cost_total' => 'integer',
            'cost_is_final' => 'boolean',
            'unavailable_total' => 'integer',
            'items_count' => 'integer',
            'area_id' => 'integer',
            'placed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'purchasing_at' => 'datetime',
            'out_for_delivery_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Customer-facing URLs use the order number: /orders/ORD-251111-0001.
     */
    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    /**
     * @return HasMany<OrderEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->orderBy('id');
    }

    /**
     * True while the bill may still change: undelivered order with a weighted item not yet weighed.
     * Requires the `items` relation to be loaded.
     */
    public function isTotalEstimated(): bool
    {
        if ($this->status === OrderStatus::Delivered || $this->status === OrderStatus::Cancelled) {
            return false;
        }

        return $this->items->contains(
            fn (OrderItem $item): bool => $item->isWeighted() && $item->status !== OrderItemStatus::Unavailable && $item->actual_weight_grams === null,
        );
    }
}
