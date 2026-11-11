<?php

use App\Settings\StoreSettings;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

it('renders the home page with shared store info', function () {
    app(StoreSettings::class)->save(['store_name' => 'তাজা বাজার', 'store_phone' => '01712345678']);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('store.name', 'তাজা বাজার')
            ->where('store.phone', '01712345678'));
});

it('prints the selected theme colors in the page head', function () {
    app(StoreSettings::class)->save(['theme' => 'midnight_saffron']);

    $this->get('/')
        ->assertOk()
        ->assertSee('--c-primary:#14213D;', escape: false);
});
