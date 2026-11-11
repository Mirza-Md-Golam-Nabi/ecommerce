<?php

use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Livewire\Livewire;

it('renders edit and delete as icon-only buttons with tooltips', function () {
    $edit = EditAction::make();
    $delete = DeleteAction::make();

    expect($edit->isIconButton())->toBeTrue()
        ->and($edit->getTooltip())->toBe('এডিট')
        ->and($delete->isIconButton())->toBeTrue()
        ->and($delete->getTooltip())->toBe('মুছুন');
});

it('applies the icon-only style inside resource tables', function () {
    $this->actingAs(User::factory()->create());
    $product = Product::factory()->create();

    Livewire::test(ListProducts::class)
        ->assertTableActionExists('edit', fn (EditAction $action): bool => $action->isIconButton(), $product)
        ->assertTableActionExists('delete', fn (DeleteAction $action): bool => $action->isIconButton(), $product);
});
