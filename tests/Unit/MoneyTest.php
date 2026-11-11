<?php

use App\Support\Money;

it('formats poisha as taka', function (int $poisha, string $expected) {
    expect(Money::format($poisha))->toBe($expected);
})->with([
    'whole taka' => [8500, '৳85'],
    'with poisha' => [16640, '৳166.40'],
    'thousands' => [125050, '৳1,250.50'],
    'zero' => [0, '৳0'],
    'negative' => [-500, '-৳5'],
]);

it('formats with bangla digits', function () {
    expect(Money::format(125050, bangla: true))->toBe('৳১,২৫০.৫০');
});

it('converts between taka and poisha without float errors', function () {
    expect(Money::toPoisha('85.50'))->toBe(8550)
        ->and(Money::toPoisha(0.29))->toBe(29)
        ->and(Money::toPoisha(60))->toBe(6000)
        ->and(Money::toTaka(8550))->toBe(85.5);
});
