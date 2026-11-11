<?php

use App\Models\Category;
use App\Models\Product;

it('sets path and depth for a nested tree', function () {
    $food = Category::factory()->create();
    $cooking = Category::factory()->childOf($food)->create();
    $spices = Category::factory()->childOf($cooking)->create();

    expect($food->fresh()->path)->toBe("/{$food->id}/")
        ->and($food->fresh()->depth)->toBe(0)
        ->and($cooking->fresh()->path)->toBe("/{$food->id}/{$cooking->id}/")
        ->and($spices->fresh()->path)->toBe("/{$food->id}/{$cooking->id}/{$spices->id}/")
        ->and($spices->fresh()->depth)->toBe(2);
});

it('keeps the leaf flag in sync when children are added and removed', function () {
    $food = Category::factory()->create();

    expect($food->fresh()->is_leaf)->toBeTrue();

    $cooking = Category::factory()->childOf($food)->create();

    expect($food->fresh()->is_leaf)->toBeFalse()
        ->and($cooking->fresh()->is_leaf)->toBeTrue();

    $cooking->delete();

    expect($food->fresh()->is_leaf)->toBeTrue();
});

it('moves a subtree and rewrites descendant paths', function () {
    $food = Category::factory()->create();
    $cleaning = Category::factory()->create();
    $cooking = Category::factory()->childOf($food)->create();
    $spices = Category::factory()->childOf($cooking)->create();

    $cooking->update(['parent_id' => $cleaning->id]);

    expect($spices->fresh()->path)->toBe("/{$cleaning->id}/{$cooking->id}/{$spices->id}/")
        ->and($spices->fresh()->depth)->toBe(2)
        ->and($food->fresh()->is_leaf)->toBeTrue()
        ->and($cleaning->fresh()->is_leaf)->toBeFalse();
});

it('moves a subtree to the top level', function () {
    $food = Category::factory()->create();
    $cooking = Category::factory()->childOf($food)->create();
    $spices = Category::factory()->childOf($cooking)->create();

    $cooking->update(['parent_id' => null]);

    expect($cooking->fresh()->depth)->toBe(0)
        ->and($spices->fresh()->path)->toBe("/{$cooking->id}/{$spices->id}/")
        ->and($spices->fresh()->depth)->toBe(1);
});

it('refuses to move a category inside its own subtree', function () {
    $food = Category::factory()->create();
    $cooking = Category::factory()->childOf($food)->create();

    $food->update(['parent_id' => $cooking->id]);
})->throws(LogicException::class);

it('refuses to add a sub-category under a category that has products', function () {
    $spices = Category::factory()->create();
    Product::factory()->for($spices)->create();

    Category::factory()->childOf($spices)->create();
})->throws(LogicException::class);

it('can be deleted only when empty', function () {
    $food = Category::factory()->create();
    $cooking = Category::factory()->childOf($food)->create();

    expect($food->canBeDeleted())->toBeFalse()
        ->and($cooking->canBeDeleted())->toBeTrue();

    Product::factory()->for($cooking)->create();

    expect($cooking->canBeDeleted())->toBeFalse();
});
