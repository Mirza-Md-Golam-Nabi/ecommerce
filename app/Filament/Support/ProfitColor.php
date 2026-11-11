<?php

namespace App\Filament\Support;

/**
 * Filament color for a profit amount: green when ≥ 0, red when negative.
 */
final class ProfitColor
{
    public static function for(int $amount): string
    {
        return $amount >= 0 ? 'success' : 'danger';
    }
}
