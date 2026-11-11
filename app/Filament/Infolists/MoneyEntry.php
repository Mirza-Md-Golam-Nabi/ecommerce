<?php

namespace App\Filament\Infolists;

use App\Support\Money;
use Filament\Infolists\Components\TextEntry;

/**
 * Infolist entry for a poisha amount, shown as taka.
 */
final class MoneyEntry
{
    public static function make(string $name): TextEntry
    {
        return TextEntry::make($name)
            ->formatStateUsing(fn (mixed $state): string => Money::format((int) $state));
    }
}
