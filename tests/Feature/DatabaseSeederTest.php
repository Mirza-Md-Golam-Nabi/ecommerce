<?php

use App\Models\Area;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Catalog\CategoryTree;
use Database\Seeders\DatabaseSeeder;

it('seeds an admin, areas and a consistent demo catalog', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(1)
        ->and(Area::query()->count())->toBeGreaterThan(0)
        ->and(Product::query()->count())->toBeGreaterThan(30);

    $spices = Category::query()->where('slug', 'spices')->firstOrFail();

    expect(app(CategoryTree::class)->pathLabel($spices->id))->toBe('খাবার › রান্না › মসলা')
        ->and($spices->is_leaf)->toBeTrue()
        ->and($spices->depth)->toBe(2);

    $nonLeafCategoryIds = Category::query()->where('is_leaf', false)->pluck('id');

    expect(Product::query()->whereIn('category_id', $nonLeafCategoryIds)->exists())->toBeFalse();
});

it('can be run twice without duplicating data', function () {
    $this->seed(DatabaseSeeder::class);
    $counts = [Category::query()->count(), Product::query()->count(), Area::query()->count()];

    $this->seed(DatabaseSeeder::class);

    expect([Category::query()->count(), Product::query()->count(), Area::query()->count()])->toBe($counts);
});
