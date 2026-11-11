<?php

use App\Filament\Resources\Areas\Pages\ManageAreas;
use App\Models\Area;
use App\Models\User;
use Filament\Actions\CreateAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists and creates areas', function () {
    $areas = Area::factory()->count(2)->create();

    Livewire::test(ManageAreas::class)
        ->assertCanSeeTableRecords($areas)
        ->callAction(CreateAction::class, ['name' => 'ধানমন্ডি', 'is_active' => true])
        ->assertHasNoActionErrors();

    expect(Area::query()->where('name', 'ধানমন্ডি')->exists())->toBeTrue();
});

it('lists only active areas, by name', function () {
    Area::factory()->create(['name' => 'খ']);
    Area::factory()->create(['name' => 'ক']);
    Area::factory()->inactive()->create(['name' => 'গ']);

    expect(Area::query()->active()->pluck('name')->all())->toBe(['ক', 'খ']);
});
