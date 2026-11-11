<?php

use App\Models\Category;
use App\Models\Product;
use App\Settings\StoreSettings;

it('can only belong to a leaf category', function () {
    $food = Category::factory()->create();
    Category::factory()->childOf($food)->create();

    Product::factory()->for($food)->create();
})->throws(LogicException::class);

it('requires a nominal weight for weighted products', function () {
    Product::factory()->weighted()->create(['nominal_weight_grams' => null]);
})->throws(LogicException::class);

it('clears nominal weight for fixed products', function () {
    $product = Product::factory()->create(['nominal_weight_grams' => 500]);

    expect($product->nominal_weight_grams)->toBeNull();
});

it('calculates the discount percent', function () {
    $product = Product::factory()->make(['price' => 8500, 'compare_at_price' => 10000]);

    expect($product->discountPercent())->toBe(15)
        ->and(Product::factory()->make(['price' => 8500, 'compare_at_price' => null])->discountPercent())->toBeNull()
        ->and(Product::factory()->make(['price' => 8500, 'compare_at_price' => 8000])->discountPercent())->toBeNull();
});

it('uses its own quantity limit before the store default', function () {
    $settings = app(StoreSettings::class);

    expect(Product::factory()->make(['max_per_order' => null])->maxQuantityPerOrder($settings))->toBeNull();

    $settings->save(['default_max_qty_per_product' => 10]);

    expect(Product::factory()->make(['max_per_order' => null])->maxQuantityPerOrder($settings))->toBe(10)
        ->and(Product::factory()->make(['max_per_order' => 3])->maxQuantityPerOrder($settings))->toBe(3);
});
