<?php

namespace App\Support;

/**
 * Bangladeshi mobile number normalization. Canonical form: 11 digits `01XXXXXXXXX`.
 */
final class PhoneNumber
{
    private const PATTERN = '/^01[3-9]\d{8}$/';

    /**
     * Normalize user input to `01XXXXXXXXX`, or null when it is not a valid BD mobile number.
     * Accepts spaces, dashes, `+88`/`88` prefix and Bangla digits.
     */
    public static function normalize(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', BanglaNumber::toLatin($input)) ?? '';

        if (str_starts_with($digits, '880')) {
            $digits = substr($digits, 2);
        }

        return preg_match(self::PATTERN, $digits) ? $digits : null;
    }

    public static function isValid(?string $input): bool
    {
        return self::normalize($input) !== null;
    }
}
