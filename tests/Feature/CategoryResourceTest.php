<?php

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists categories', function () {
    $categories = Category::factory()->count(3)->create();

    Livewire::test(ListCategories::class)
        ->assertOk()
        ->assertCanSeeTableRecords($categories);
});

it('creates a sub-category', function () {
    $food = Category::factory()->create();

    Livewire::test(CreateCategory::class)
        ->fillForm([
            'name' => 'মসলা',
            'slug' => 'spices',
            'parent_id' => $food->id,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $spices = Category::query()->where('slug', 'spices')->firstOrFail();

    expect($spices->parent_id)->toBe($food->id)
        ->and($spices->depth)->toBe(1)
        ->and($food->fresh()->is_leaf)->toBeFalse();
});

it('validates slug format and uniqueness', function () {
    Product::factory()->create(['slug' => 'onion']);

    Livewire::test(CreateCategory::class)
        ->fillForm(['name' => 'ক', 'slug' => 'Not Valid'])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'regex']);

    Livewire::test(CreateCategory::class)
        ->fillForm(['name' => 'ক', 'slug' => 'onion'])
        ->call('create')
        ->assertHasFormErrors(['slug']);

    Livewire::test(CreateCategory::class)
        ->fillForm(['name' => 'ক', 'slug' => 'checkout'])
        ->call('create')
        ->assertHasFormErrors(['slug']);
});

it('does not allow a product-holding category as parent', function () {
    $spices = Category::factory()->create();
    Product::factory()->for($spices)->create();

    Livewire::test(CreateCategory::class)
        ->fillForm(['name' => 'ক', 'slug' => 'new-category', 'parent_id' => $spices->id])
        ->call('create')
        ->assertHasFormErrors(['parent_id']);
});

it('does not allow moving a category under its own child', function () {
    $food = Category::factory()->create();
    $cooking = Category::factory()->childOf($food)->create();

    Livewire::test(EditCategory::class, ['record' => $food->getRouteKey()])
        ->fillForm(['parent_id' => $cooking->id])
        ->call('save')
        ->assertHasFormErrors(['parent_id']);
});

it('keeps its own slug when editing', function () {
    $food = Category::factory()->create(['slug' => 'food']);

    Livewire::test(EditCategory::class, ['record' => $food->getRouteKey()])
        ->fillForm(['name' => 'খাদ্য'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($food->fresh()->name)->toBe('খাদ্য');
});

it('hides delete for categories that are not empty', function () {
    $food = Category::factory()->create();
    Category::factory()->childOf($food)->create();

    Livewire::test(EditCategory::class, ['record' => $food->getRouteKey()])
        ->assertActionHidden(DeleteAction::class);
});
