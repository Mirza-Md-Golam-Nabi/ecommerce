<?php

use App\Enums\OrderItemStatus;
use App\Enums\SaleType;
use App\Models\OrderItem;
use App\Services\Pricing\LinePriceCalculator;

function orderLine(array $attributes): OrderItem
{
    return (new OrderItem)->forceFill([
        'sale_type' => SaleType::Fixed,
        'nominal_weight_grams' => null,
        'unit_price' => 8000,
        'quantity' => 2,
        'fulfilled_quantity' => null,
        'actual_weight_grams' => null,
        'estimated_unit_cost' => 6000,
        'actual_unit_cost' => null,
        'status' => OrderItemStatus::Pending,
        ...$attributes,
    ]);
}

it('prices fixed items by quantity', function () {
    $calculator = new LinePriceCalculator;

    expect($calculator->lineTotal(orderLine([])))->toBe(16000)
        ->and($calculator->lineCost(orderLine([])))->toBe(12000);
});

it('estimates weighted items until weighed, then uses the actual weight', function () {
    $calculator = new LinePriceCalculator;
    $line = orderLine(['sale_type' => SaleType::Weighted, 'nominal_weight_grams' => 1000]);

    expect($calculator->lineTotal($line))->toBe(16000);

    $line->actual_weight_grams = 2080;

    expect($calculator->lineTotal($line))->toBe(16640)
        ->and($calculator->lineCost($line))->toBe(12480);
});

it('uses actual cost when entered', function () {
    expect((new LinePriceCalculator)->lineCost(orderLine(['actual_unit_cost' => 7000])))->toBe(14000);
});

it('charges nothing for unavailable items and reports them as lost value', function () {
    $calculator = new LinePriceCalculator;
    $line = orderLine(['status' => OrderItemStatus::Unavailable]);

    expect($calculator->lineTotal($line))->toBe(0)
        ->and($calculator->lineCost($line))->toBe(0)
        ->and($calculator->missingValue($line))->toBe(16000);
});

it('charges only the fulfilled quantity of a partial item', function () {
    $calculator = new LinePriceCalculator;
    $line = orderLine(['status' => OrderItemStatus::Partial, 'quantity' => 5, 'fulfilled_quantity' => 3]);

    expect($calculator->lineTotal($line))->toBe(24000)
        ->and($calculator->missingValue($line))->toBe(16000);
});
