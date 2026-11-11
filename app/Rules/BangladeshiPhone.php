<?php

namespace App\Rules;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Valid Bangladeshi mobile number (any common format; normalize with PhoneNumber::normalize()).
 */
class BangladeshiPhone implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! PhoneNumber::isValid($value)) {
            $fail('সঠিক মোবাইল নম্বর দিন (যেমন: ০১৭১২৩৪৫৬৭৮)।');
        }
    }
}
