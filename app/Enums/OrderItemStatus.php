<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderItemStatus: string implements HasColor, HasLabel
{
    /** Not yet bought from the market. */
    case Pending = 'pending';

    /** Bought in full. */
    case Fulfilled = 'fulfilled';

    /** Only part of the ordered quantity could be bought. */
    case Partial = 'partial';

    /** Not found in the market; kept for reporting, excluded from the bill. */
    case Unavailable = 'unavailable';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'অপেক্ষমান',
            self::Fulfilled => 'সম্পূর্ণ',
            self::Partial => 'আংশিক',
            self::Unavailable => 'পাওয়া যায়নি',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Fulfilled => 'success',
            self::Partial => 'warning',
            self::Unavailable => 'danger',
        };
    }

    public function isBillable(): bool
    {
        return $this !== self::Unavailable;
    }
}
