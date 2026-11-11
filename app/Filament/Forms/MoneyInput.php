<?php

namespace App\Filament\Forms;

use App\Support\Money;
use Filament\Forms\Components\TextInput;

/**
 * Taka input for a poisha column: the admin types taka (85.50), the database stores poisha (8550).
 */
final class MoneyInput
{
    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->prefix('৳')
            ->numeric()
            ->minValue(0)
            ->step(0.01)
            ->formatStateUsing(fn (mixed $state): ?float => blank($state) ? null : Money::toTaka((int) $state))
            ->dehydrateStateUsing(fn (mixed $state): ?int => blank($state) ? null : Money::toPoisha($state));
    }
}
