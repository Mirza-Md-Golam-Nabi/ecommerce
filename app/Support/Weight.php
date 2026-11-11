<?php

namespace App\Support;

/**
 * Weight helpers. All weights in the app are integers in grams.
 */
final class Weight
{
    /**
     * Grams for display in kg: 2080 → "2.08 কেজি", 1000 → "1 কেজি".
     */
    public static function format(int $grams): string
    {
        return rtrim(rtrim(number_format($grams / 1000, 3), '0'), '.').' কেজি';
    }
}
