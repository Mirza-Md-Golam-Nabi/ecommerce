<?php

namespace App\Enums;

use App\Models\Customer;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * COD risk badge shown to the admin before confirming an order (no OTP — docs/03-order-flow.md).
 */
enum CustomerRisk: string implements HasColor, HasLabel
{
    case New = 'new';
    case Trusted = 'trusted';
    case Risky = 'risky';

    public static function for(Customer $customer): self
    {
        if ($customer->is_blocked || ($customer->cancelled_count >= 2 && $customer->cancelled_count >= $customer->delivered_count)) {
            return self::Risky;
        }

        return $customer->delivered_count > 0 ? self::Trusted : self::New;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'নতুন কাস্টমার',
            self::Trusted => 'বিশ্বস্ত',
            self::Risky => 'ঝুঁকিপূর্ণ',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Trusted => 'success',
            self::Risky => 'danger',
        };
    }
}
