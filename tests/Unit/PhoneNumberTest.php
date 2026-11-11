<?php

use App\Support\PhoneNumber;

it('normalizes valid bangladeshi mobile numbers', function (string $input) {
    expect(PhoneNumber::normalize($input))->toBe('01712345678');
})->with([
    'plain' => '01712345678',
    'plus country code' => '+8801712345678',
    'country code' => '8801712345678',
    'dashes' => '017-1234-5678',
    'spaces' => '017 1234 5678',
    'bangla digits' => '০১৭১২৩৪৫৬৭৮',
]);

it('rejects invalid numbers', function (?string $input) {
    expect(PhoneNumber::normalize($input))->toBeNull()
        ->and(PhoneNumber::isValid($input))->toBeFalse();
})->with([
    'too short' => '0171234567',
    'too long' => '017123456789',
    'invalid operator' => '01212345678',
    'landline' => '029876543',
    'letters' => 'abc',
    'null' => null,
]);
