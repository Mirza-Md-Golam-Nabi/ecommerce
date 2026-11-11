<?php

namespace App\Filament\Tables;

use App\Support\Money;
use Filament\Tables\Columns\TextColumn;

/**
 * Table column for a poisha amount, shown as taka ("৳1,250.50"; "—" when empty).
 * Use with ->state() for computed amounts.
 */
final class MoneyColumn
{
    public static function make(string $name): TextColumn
    {
        return TextColumn::make($name)
            ->formatStateUsing(fn (mixed $state): string => Money::format((int) $state))
            ->placeholder('—');
    }
}
