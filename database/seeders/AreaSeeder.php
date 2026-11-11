<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

/**
 * Demo delivery areas (Dhaka). Replace with the real service areas from the admin panel.
 */
class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = ['ধানমন্ডি', 'মোহাম্মদপুর', 'মিরপুর', 'উত্তরা', 'গুলশান', 'বনানী', 'বাড্ডা', 'রামপুরা', 'মালিবাগ', 'মতিঝিল'];

        foreach ($areas as $name) {
            Area::query()->firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
