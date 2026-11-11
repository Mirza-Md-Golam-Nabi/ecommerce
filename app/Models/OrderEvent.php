<?php

namespace App\Models;

use App\Enums\OrderEventType;
use Database\Factories\OrderEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only audit trail of an order. `user_id` is the admin who made the change (null = customer/system).
 *
 * @property int $id
 * @property int $order_id
 * @property int|null $order_item_id
 * @property OrderEventType $type
 * @property array<string, mixed>|null $from
 * @property array<string, mixed>|null $to
 * @property string|null $note
 * @property int|null $user_id
 */
#[Fillable(['order_id', 'order_item_id', 'type', 'from', 'to', 'note', 'user_id'])]
class OrderEvent extends Model
{
    /** @use HasFactory<OrderEventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => OrderEventType::class,
            'from' => 'array',
            'to' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
