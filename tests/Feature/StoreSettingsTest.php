<?php

use App\Models\Setting;
use App\Settings\StoreSettings;

it('returns defaults when nothing is stored', function () {
    $settings = app(StoreSettings::class);

    expect($settings->storeName())->toBe(config('app.name'))
        ->and($settings->defaultDeliveryCharge())->toBe(6000)
        ->and($settings->defaultMaxQtyPerProduct())->toBeNull()
        ->and($settings->maxPendingOrdersPerCustomer())->toBe(3)
        ->and($settings->theme())->toBeNull();
});

it('saves values and refreshes the cache', function () {
    $settings = app(StoreSettings::class);
    $settings->all();

    $settings->save([
        'store_name' => 'তাজা বাজার',
        'default_delivery_charge' => 5000,
        'default_max_qty_per_product' => 10,
    ]);

    expect($settings->storeName())->toBe('তাজা বাজার')
        ->and($settings->defaultDeliveryCharge())->toBe(5000)
        ->and($settings->defaultMaxQtyPerProduct())->toBe(10)
        ->and(Setting::query()->count())->toBe(3);
});

it('ignores unknown keys', function () {
    app(StoreSettings::class)->save(['not_a_setting' => 'x']);

    expect(Setting::query()->count())->toBe(0);
});
