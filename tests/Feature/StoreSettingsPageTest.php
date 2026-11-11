<?php

use App\Filament\Pages\StoreSettings as StoreSettingsPage;
use App\Models\User;
use App\Settings\StoreSettings;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('shows current settings', function () {
    Livewire::test(StoreSettingsPage::class)
        ->assertOk()
        ->assertSchemaStateSet([
            'theme' => 'emerald_gold',
            'default_delivery_charge' => 60,
            'max_pending_orders_per_customer' => 3,
        ]);
});

it('saves settings, converting taka to poisha', function () {
    Livewire::test(StoreSettingsPage::class)
        ->fillForm([
            'store_name' => 'তাজা বাজার',
            'theme' => 'midnight_saffron',
            'default_delivery_charge' => 49.5,
            'default_max_qty_per_product' => 12,
            'max_pending_orders_per_customer' => 2,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(StoreSettings::class);
    $settings->flush();

    expect($settings->storeName())->toBe('তাজা বাজার')
        ->and($settings->theme())->toBe('midnight_saffron')
        ->and($settings->defaultDeliveryCharge())->toBe(4950)
        ->and($settings->defaultMaxQtyPerProduct())->toBe(12)
        ->and($settings->maxPendingOrdersPerCustomer())->toBe(2);
});

it('rejects an unknown theme', function () {
    Livewire::test(StoreSettingsPage::class)
        ->fillForm(['theme' => 'hacker_theme'])
        ->call('save')
        ->assertHasFormErrors(['theme']);
});
