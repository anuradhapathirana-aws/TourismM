<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            ProvinceDistrictSeeder::class,
            PlaceTypeThemeSeeder::class,
            TourCategorySeeder::class,
            ActivitySeeder::class,
        ]);

        // Sample content only outside production
        if (! app()->isProduction()) {
            $this->call([DemoLocationSeeder::class, DemoTourSeeder::class]);
        }
    }
}
