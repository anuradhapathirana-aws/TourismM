<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ThemeSide;
use App\Models\PlaceType;
use App\Models\Theme;
use Illuminate\Database\Seeder;

/** Starter lookup data the admin can edit afterwards. Idempotent. */
final class PlaceTypeThemeSeeder extends Seeder
{
    /** name => Bootstrap icon */
    public const PLACE_TYPES = [
        'Temple' => 'bank',
        'Heritage Site' => 'columns-gap',
        'Waterfall' => 'droplet',
        'Beach' => 'umbrella',
        'National Park' => 'tree',
        'Mountain & Hike' => 'triangle',
        'Viewpoint' => 'binoculars',
        'Lake & River' => 'water',
        'Garden' => 'flower1',
        'City Sight' => 'buildings',
        'Food & Market' => 'cup-hot',
        'Adventure Activity' => 'lightning',
    ];

    /** slug => [title, side, unsplash photo id] (same as the frontend mock data) */
    public const THEMES = [
        'beaches' => ['Popular Beaches', ThemeSide::Left, 'photo-1552055568-f8c4fb8c6320'],
        'wildlife' => ['Wildlife & Nature', ThemeSide::Left, 'photo-1549366021-9f761d450615'],
        'adventure' => ['Adventure', ThemeSide::Left, 'photo-1581791534721-e599df4417f7'],
        'culture' => ['History & Culture', ThemeSide::Right, 'photo-1588598198321-9735fd52455b'],
        'lesser-travelled' => ['Lesser Travelled', ThemeSide::Right, 'photo-1469474968028-56623f02e42e'],
        'gastronomy' => ['Gastronomy', ThemeSide::Right, 'photo-1585937421612-70a008356fbe'],
    ];

    public function run(): void
    {
        $i = 0;
        foreach (self::PLACE_TYPES as $name => $icon) {
            PlaceType::firstOrCreate(['name' => $name], ['icon' => $icon, 'sort_order' => ++$i]);
        }

        $i = 0;
        foreach (self::THEMES as $slug => [$title, $side, $photo]) {
            $theme = Theme::firstOrCreate(['slug' => $slug], ['title' => $title, 'side' => $side, 'sort_order' => ++$i]);
            DemoImages::attach($theme, 'image', $photo, 600);
        }
    }
}
