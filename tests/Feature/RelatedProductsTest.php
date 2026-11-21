<?php

use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\RelatedProducts;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->food = Category::factory()->create(['slug' => 'food']);
    $this->milk = Category::factory()->childOf($this->food)->create(['slug' => 'milk']);
    $this->product = Product::factory()->for($this->milk)->create(['slug' => 'diploma-milk-powder']);
});

it('loads related products as a deferred prop on the product page', function () {
    Product::factory()->for($this->milk)->create(['slug' => 'dano']);

    $this->get('/diploma-milk-powder')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Product')
            ->missing('relatedProducts')
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->has('relatedProducts', 1)
                ->where('relatedProducts.0.slug', 'dano')));
});

it('lists same-category products first, then sibling categories, never the product itself', function () {
    $tea = Category::factory()->childOf($this->food)->create();
    $sibling = Product::factory()->for($tea)->create(['total_sold' => 999]);
    $sameCategory = Product::factory()->for($this->milk)->create(['total_sold' => 1]);

    $slugs = array_column(app(RelatedProducts::class)->for($this->product), 'slug');

    expect($slugs)->toBe([$sameCategory->slug, $sibling->slug]);
});

it('puts available products first and skips inactive or hidden ones', function () {
    $unavailable = Product::factory()->for($this->milk)->unavailable()->create(['total_sold' => 999]);
    $available = Product::factory()->for($this->milk)->create();
    Product::factory()->for($this->milk)->inactive()->create();
    $hidden = Category::factory()->childOf($this->food)->inactive()->create();
    Product::factory()->for($hidden)->create();
    Product::factory()->for(Category::factory()->create())->create();

    $slugs = array_column(app(RelatedProducts::class)->for($this->product), 'slug');

    expect($slugs)->toBe([$available->slug, $unavailable->slug]);
});

it('limits the number of related products', function () {
    Product::factory()->for($this->milk)->count(RelatedProducts::LIMIT + 3)->create();

    expect(app(RelatedProducts::class)->for($this->product))->toHaveCount(RelatedProducts::LIMIT);
});
