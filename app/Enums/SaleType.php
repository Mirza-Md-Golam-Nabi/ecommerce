<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SaleType: string implements HasLabel
{
    /** Packaged product with a fixed price per unit. */
    case Fixed = 'fixed';

    /** Sold by weight (vegetables, fish, meat); final weight & price set by admin. */
    case Weighted = 'weighted';

    public function getLabel(): string
    {
        return match ($this) {
            self::Fixed => 'প্যাকেট / নির্দিষ্ট দাম',
            self::Weighted => 'ওজন অনুযায়ী',
        };
    }
}
