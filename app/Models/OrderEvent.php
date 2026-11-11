<?php

namespace App\Models;

use App\Enums\CancelReason;
use App\Enums\OrderEventType;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Support\BanglaNumber;
use App\Support\Money;
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

    /**
     * One readable Bangla line for the admin timeline, e.g. "অপেক্ষমান → নিশ্চিত" or "৳৬০ → ৳৪০".
     */
    public function summary(): string
    {
        $describe = function (?array $values): string {
            if ($values === null || $values === []) {
                return '—';
            }

            return collect($values)->map(fn (mixed $value, string $key): string => match ($key) {
                'status' => OrderStatus::tryFrom((string) $value)?->getLabel() ?? OrderItemStatus::tryFrom((string) $value)?->getLabel() ?? (string) $value,
                'cancel_reason' => CancelReason::tryFrom((string) $value)?->getLabel() ?? (string) $value,
                'delivery_charge', 'unit_cost' => $value === null ? '—' : Money::format((int) $value, bangla: true),
                'amount' => $value === null ? '—' : BanglaNumber::digits((int) $value),
                default => (string) $value,
            })->implode(', ');
        };

        if ($this->from === null) {
            return $describe($this->to);
        }

        return $describe($this->from).' → '.$describe($this->to);
    }
}
