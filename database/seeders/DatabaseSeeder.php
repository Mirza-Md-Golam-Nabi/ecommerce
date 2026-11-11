<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Demo data is never seeded in production.
     *
     * Model events must stay enabled: the category tree (path, depth, is_leaf) is maintained by them.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call([
                AreaSeeder::class,
                CatalogSeeder::class,
            ]);
        }
    }
}
