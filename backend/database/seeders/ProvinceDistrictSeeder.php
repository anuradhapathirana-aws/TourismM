<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\District;
use App\Models\Province;
use Illuminate\Database\Seeder;

/** Reference data: Sri Lanka's 9 provinces and 25 districts. Idempotent. */
final class ProvinceDistrictSeeder extends Seeder
{
    public const DATA = [
        'Western' => ['Colombo', 'Gampaha', 'Kalutara'],
        'Central' => ['Kandy', 'Matale', 'Nuwara Eliya'],
        'Southern' => ['Galle', 'Matara', 'Hambantota'],
        'Northern' => ['Jaffna', 'Kilinochchi', 'Mannar', 'Vavuniya', 'Mullaitivu'],
        'Eastern' => ['Batticaloa', 'Ampara', 'Trincomalee'],
        'North Western' => ['Kurunegala', 'Puttalam'],
        'North Central' => ['Anuradhapura', 'Polonnaruwa'],
        'Uva' => ['Badulla', 'Monaragala'],
        'Sabaragamuwa' => ['Ratnapura', 'Kegalle'],
    ];

    public function run(): void
    {
        foreach (self::DATA as $provinceName => $districts) {
            $province = Province::firstOrCreate(['name' => $provinceName]);

            foreach ($districts as $districtName) {
                District::firstOrCreate(['name' => $districtName], ['province_id' => $province->id]);
            }
        }
    }
}
