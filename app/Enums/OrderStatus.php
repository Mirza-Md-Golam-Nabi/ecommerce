<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Order lifecycle (see docs/03-order-flow.md):
 * pending → confirmed → purchasing → out_for_delivery → delivered,
 * and cancelled from any state before delivered.
 */
enum OrderStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Purchasing = 'purchasing';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Purchasing, self::Cancelled],
            self::Purchasing => [self::OutForDelivery, self::Cancelled],
            self::OutForDelivery => [self::Delivered, self::Cancelled],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return $this === self::Delivered || $this === self::Cancelled;
    }

    /**
     * Statuses of orders still in progress (not delivered/cancelled).
     *
     * @return list<self>
     */
    public static function active(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status): bool => ! $status->isFinal()));
    }

    /**
     * Customers may cancel their own order only while it is pending.
     */
    public function isCancellableByCustomer(): bool
    {
        return $this === self::Pending;
    }

    /**
     * Name of the orders table timestamp column set when entering this status.
     */
    public function timestampColumn(): ?string
    {
        return match ($this) {
            self::Pending => 'placed_at',
            self::Confirmed => 'confirmed_at',
            self::Purchasing => 'purchasing_at',
            self::OutForDelivery => 'out_for_delivery_at',
            self::Delivered => 'delivered_at',
            self::Cancelled => 'cancelled_at',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'অপেক্ষমান',
            self::Confirmed => 'নিশ্চিত',
            self::Purchasing => 'কেনাকাটা চলছে',
            self::OutForDelivery => 'ডেলিভারির পথে',
            self::Delivered => 'ডেলিভারি সম্পন্ন',
            self::Cancelled => 'বাতিল',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'info',
            self::Purchasing => 'primary',
            self::OutForDelivery => 'info',
            self::Delivered => 'success',
            self::Cancelled => 'danger',
        };
    }
}
