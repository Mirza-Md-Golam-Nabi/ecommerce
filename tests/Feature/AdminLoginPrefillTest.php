<?php

use App\Filament\Pages\Auth\Login;
use Livewire\Livewire;

it('pre-fills the demo admin credentials in the local environment', function () {
    app()->detectEnvironment(fn (): string => 'local');

    Livewire::test(Login::class)
        ->assertSchemaStateSet([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
});

it('leaves the login form empty in production', function () {
    app()->detectEnvironment(fn (): string => 'production');

    Livewire::test(Login::class)
        ->assertSchemaStateSet([
            'email' => null,
            'password' => null,
        ]);
});
