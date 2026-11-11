<?php

use App\Enums\SaleType;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->spices = Category::factory()->create(['name' => 'মসলা']);
});

it('lists products', function () {
    $products = Product::factory()->count(3)->for($this->spices)->create();

    Livewire::test(ListProducts::class)
        ->assertOk()
        ->assertCanSeeTableRecords($products);
});

it('creates a fixed product, storing taka as poisha', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'রাঁধুনি হলুদ গুঁড়া',
            'slug' => 'radhuni-holud-gura-200g',
            'category_id' => $this->spices->id,
            'sale_type' => SaleType::Fixed,
            'unit_label' => '২০০ গ্রাম',
            'price' => 85.5,
            'compare_at_price' => 95,
            'estimated_cost' => 72,
            'is_active' => true,
            'is_available' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::query()->where('slug', 'radhuni-holud-gura-200g')->firstOrFail();

    expect($product->price)->toBe(8550)
        ->and($product->compare_at_price)->toBe(9500)
        ->and($product->estimated_cost)->toBe(7200)
        ->and($product->sale_type)->toBe(SaleType::Fixed);
});

it('requires nominal weight for weighted products', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'দেশি পেঁয়াজ',
            'slug' => 'deshi-peyaj',
            'category_id' => $this->spices->id,
            'sale_type' => SaleType::Weighted,
            'unit_label' => '১ কেজি (± ৫০ গ্রাম)',
            'price' => 80,
            'estimated_cost' => 65,
        ])
        ->call('create')
        ->assertHasFormErrors(['nominal_weight_grams' => 'required']);
});

it('creates a weighted product', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'দেশি পেঁয়াজ',
            'slug' => 'deshi-peyaj',
            'category_id' => $this->spices->id,
            'sale_type' => SaleType::Weighted,
            'unit_label' => '১ কেজি (± ৫০ গ্রাম)',
            'nominal_weight_grams' => 1000,
            'price' => 80,
            'estimated_cost' => 65,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Product::query()->where('slug', 'deshi-peyaj')->value('nominal_weight_grams'))->toBe(1000);
});

it('rejects a non-leaf category and a compare price below the price', function () {
    $food = Category::factory()->create();
    Category::factory()->childOf($food)->create();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'চিনি',
            'slug' => 'chini-1kg',
            'category_id' => $food->id,
            'sale_type' => SaleType::Fixed,
            'unit_label' => '১ কেজি',
            'price' => 140,
            'compare_at_price' => 120,
            'estimated_cost' => 120,
        ])
        ->call('create')
        ->assertHasFormErrors(['category_id', 'compare_at_price']);
});

it('shows prices in taka when editing', function () {
    $product = Product::factory()->for($this->spices)->create(['price' => 8550]);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->assertSchemaStateSet(['price' => 85.5]);
});

it('marks selected products unavailable in bulk', function () {
    $products = Product::factory()->count(2)->for($this->spices)->create();

    Livewire::test(ListProducts::class)
        ->callTableBulkAction('markUnavailable', $products);

    expect(Product::query()->where('is_available', true)->count())->toBe(0);
});
