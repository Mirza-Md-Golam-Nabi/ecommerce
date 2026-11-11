<?php

namespace App\Support;

/**
 * Money helpers. All amounts in the app are integers in poisha (৳1 = 100 poisha).
 */
final class Money
{
    /**
     * Convert a taka amount (e.g. from an admin form) to poisha.
     */
    public static function toPoisha(int|float|string $taka): int
    {
        return (int) round(((float) $taka) * 100);
    }

    /**
     * Convert poisha to taka for form inputs (e.g. 8550 → 85.5).
     */
    public static function toTaka(int $poisha): float
    {
        return $poisha / 100;
    }

    /**
     * Format poisha for display: `৳85`, `৳1,250.50`. Decimals shown only when non-zero.
     * With $bangla = true digits are Bangla: `৳৮৫`.
     */
    public static function format(int $poisha, bool $bangla = false): string
    {
        $negative = $poisha < 0;
        $poisha = abs($poisha);

        $formatted = number_format(intdiv($poisha, 100));

        if ($poisha % 100 !== 0) {
            $formatted .= '.'.str_pad((string) ($poisha % 100), 2, '0', STR_PAD_LEFT);
        }

        if ($bangla) {
            $formatted = BanglaNumber::digits($formatted);
        }

        return ($negative ? '-' : '').'৳'.$formatted;
    }
}
