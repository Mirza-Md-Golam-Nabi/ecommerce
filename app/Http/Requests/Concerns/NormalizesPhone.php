<?php

namespace App\Http\Requests\Concerns;

use App\Support\PhoneNumber;

/**
 * Normalize the `phone` input to 01XXXXXXXXX before validation (invalid input is left as typed
 * so the BangladeshiPhone rule can report it).
 */
trait NormalizesPhone
{
    protected function normalizePhoneInput(string $field = 'phone'): void
    {
        $this->merge([$field => PhoneNumber::normalize($this->string($field)->toString()) ?? $this->input($field)]);
    }
}
