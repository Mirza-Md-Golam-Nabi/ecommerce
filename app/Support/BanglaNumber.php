<?php

namespace App\Support;

/**
 * Converts between Latin and Bangla digits for customer-facing text.
 */
final class BanglaNumber
{
    private const DIGITS = ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯'];

    /**
     * `"1,250.50"` → `"১,২৫০.৫০"`.
     */
    public static function digits(int|string $value): string
    {
        return strtr((string) $value, self::DIGITS);
    }

    /**
     * `"০১৭১২"` → `"01712"`.
     */
    public static function toLatin(string $value): string
    {
        return strtr($value, array_flip(self::DIGITS));
    }
}
