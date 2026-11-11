<?php

use App\Models\Category;
use App\Models\Product;
use App\Rules\UniqueStoreSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

function slugPasses(string $slug, ?Model $ignore = null): bool
{
    return Validator::make(['slug' => $slug], ['slug' => [new UniqueStoreSlug($ignore)]])->passes();
}

it('accepts a free slug', function () {
    expect(slugPasses('spices'))->toBeTrue();
});

it('rejects reserved route words', function (string $slug) {
    expect(slugPasses($slug))->toBeFalse();
})->with(['admin', 'checkout', 'search', 'Bag']);

it('shares one namespace between categories and products', function () {
    Category::factory()->create(['slug' => 'spices']);
    Product::factory()->create(['slug' => 'radhuni-holud-200g']);

    expect(slugPasses('spices'))->toBeFalse()
        ->and(slugPasses('radhuni-holud-200g'))->toBeFalse();
});

it('ignores the record being edited', function () {
    $category = Category::factory()->create(['slug' => 'spices']);

    expect(slugPasses('spices', $category))->toBeTrue();
});

it('does not ignore a product when editing a category with the same id', function () {
    $category = Category::factory()->create(['slug' => 'spices']);
    $product = Product::factory()->create(['slug' => 'onion']);

    expect(slugPasses('spices', $product))->toBeFalse();
});

it('counts soft-deleted records', function () {
    Category::factory()->create(['slug' => 'old-category'])->delete();

    expect(slugPasses('old-category'))->toBeFalse();
});
