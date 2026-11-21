<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

/**
 * Admin login page that pre-fills the demo admin credentials in the local
 * environment only. In any other environment the form starts empty.
 */
class Login extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();

        if (app()->isLocal()) {
            $this->form->fill([
                'email' => 'admin@example.com',
                'password' => 'password',
                'remember' => true,
            ]);
        }
    }
}
