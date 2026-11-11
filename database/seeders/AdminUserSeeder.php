<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates (or keeps) the first admin account for the Filament panel.
 * Credentials come from ADMIN_EMAIL / ADMIN_PASSWORD; change them before going live.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => 'Admin',
                'password' => env('ADMIN_PASSWORD', 'password'),
            ],
        );
    }
}
