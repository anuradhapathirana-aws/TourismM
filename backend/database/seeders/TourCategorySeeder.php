<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TourCategory;
use Illuminate\Database\Seeder;

/** Starter tour categories the admin can edit afterwards. Idempotent (matched by slug). */
final class TourCategorySeeder extends Seeder
{
    /** slug => [name, Bootstrap icon, short description] */
    public const CATEGORIES = [
        'beach-tours' => ['Beach Tours', 'umbrella', 'Golden sand, surf breaks and lazy days on Sri Lanka’s south, east and west coasts.'],
        'cultural-heritage-tours' => ['Cultural & Heritage Tours', 'bank', 'Ancient capitals, cave temples and UNESCO sites of the Cultural Triangle and beyond.'],
        'adventure-tours' => ['Adventure Tours', 'lightning', 'Hikes, white-water rafting, waterfalls and mountain sunrises for active travellers.'],
        'wildlife-safari-tours' => ['Wildlife & Safari Tours', 'binoculars', 'Leopards, elephants, whales and birdlife in Sri Lanka’s national parks and seas.'],
        'hill-country-tea-tours' => ['Hill Country & Tea Tours', 'cup-hot', 'Misty tea estates, scenic train rides and cool highland towns.'],
        'ayurveda-wellness-tours' => ['Ayurveda & Wellness Tours', 'flower1', 'Traditional Ayurveda treatments, yoga and quiet retreats to recharge.'],
        'honeymoon-tours' => ['Honeymoon Tours', 'heart', 'Romantic stays, private dinners and unforgettable views for couples.'],
        'family-tours' => ['Family Tours', 'people', 'Relaxed pace, kid-friendly sights and comfortable stays for the whole family.'],
        'round-tours' => ['Round Tours', 'signpost-split', 'Multi-day journeys that loop the island’s highlights with your private chauffeur-guide.'],
        'day-tours' => ['Day Tours', 'sun', 'Single-day excursions from Colombo, Kandy, Galle and the beach resorts.'],
    ];

    public function run(): void
    {
        $i = 0;
        foreach (self::CATEGORIES as $slug => [$name, $icon, $summary]) {
            TourCategory::withTrashed()->firstOrCreate(['slug' => $slug], [
                'name' => $name,
                'icon' => $icon,
                'summary' => $summary,
                'sort_order' => ++$i,
            ]);
        }
    }
}
