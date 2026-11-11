<?php

use App\Models\Category;
use App\Services\Catalog\CategoryTree;

function categoryTree(): CategoryTree
{
    return app(CategoryTree::class);
}

it('builds full path labels and lineage', function () {
    $food = Category::factory()->create(['name' => 'খাবার']);
    $cooking = Category::factory()->childOf($food)->create(['name' => 'রান্না']);
    $spices = Category::factory()->childOf($cooking)->create(['name' => 'মসলা']);

    expect(categoryTree()->pathLabel($spices->id))->toBe('খাবার › রান্না › মসলা')
        ->and(array_column(categoryTree()->lineage($spices->id), 'id'))->toBe([$food->id, $cooking->id, $spices->id]);
});

it('refreshes after categories change', function () {
    $food = Category::factory()->create(['name' => 'খাবার']);

    expect(categoryTree()->find($food->id)['name'])->toBe('খাবার');

    $food->update(['name' => 'খাদ্য']);

    expect(categoryTree()->find($food->id)['name'])->toBe('খাদ্য');
});

it('hides categories under an inactive ancestor', function () {
    $food = Category::factory()->inactive()->create();
    $cooking = Category::factory()->childOf($food)->create();

    expect(categoryTree()->isVisible($cooking->id))->toBeFalse()
        ->and(categoryTree()->children(null))->toBe([]);
});

it('lists children by name', function () {
    $food = Category::factory()->create();
    $spices = Category::factory()->childOf($food)->create(['name' => 'মসলা']);
    $rice = Category::factory()->childOf($food)->create(['name' => 'চাল']);
    $oil = Category::factory()->childOf($food)->create(['name' => 'তেল']);

    expect(array_column(categoryTree()->children($food->id), 'id'))->toBe([$rice->id, $oil->id, $spices->id]);
});

it('offers only leaf categories for products', function () {
    $food = Category::factory()->create(['name' => 'খাবার']);
    $spices = Category::factory()->childOf($food)->create(['name' => 'মসলা']);

    expect(categoryTree()->leafOptions())->toBe([$spices->id => 'খাবার › মসলা']);
});

it('excludes self, descendants and product-holding categories from parent options', function () {
    $food = Category::factory()->create();
    $cooking = Category::factory()->childOf($food)->create();
    $spices = Category::factory()->childOf($cooking)->create();
    $cleaning = Category::factory()->create();
    $soap = Category::factory()->childOf($cleaning)->create();

    $options = categoryTree()->parentOptions($cooking, [$soap->id]);

    expect(array_keys($options))->toEqualCanonicalizing([$food->id, $cleaning->id])
        ->and($options)->not->toHaveKey($spices->id);
});
