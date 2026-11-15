<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TourPace;
use App\Enums\TourStatus;
use App\Models\Activity;
use App\Models\City;
use App\Models\Destination;
use App\Models\Tour;
use App\Models\TourCategory;
use Illuminate\Database\Seeder;

/**
 * The website's 6 sample tours, planned day by day with the demo destination points (non-production only).
 * Day format: [title, details, point names[], overnight city|null, meals, km, hours, activity names[] (ActivitySeeder library)]
 * Prices: the website's "from" price is the 6+ guests rate; smaller groups pay more per person.
 */
final class DemoTourSeeder extends Seeder
{
    private const B = ['breakfast'];

    public const TOURS = [
        'cultural-triangle-heritage' => ['Cultural Triangle Heritage', 'cultural-heritage-tours', 690, true, 'photo-1588598198321-9735fd52455b',
            'Ancient kingdoms, cave temples and the Lion Rock: Sri Lanka’s cultural heart.',
            ['Sigiriya Rock Fortress at sunrise', 'Dambulla Golden Cave Temple', 'Polonnaruwa ancient city by bicycle', 'Village experience & traditional lunch', 'Temple of the Tooth, Kandy'],
            ['Private air-conditioned vehicle', 'English-speaking chauffeur-guide', '4 nights accommodation with breakfast', 'All fuel, parking & highway tolls', 'Airport transfers'],
            [
                ['Arrival & Negombo', 'Airport pick-up, relaxing evening by the lagoon.', [], 'Colombo', [], 35, 1, []],
                ['Sigiriya', 'Transfer to the Cultural Triangle, village tour and sunset at Pidurangala.', ['Pidurangala Rock'], 'Sigiriya', self::B, 165, 4, ['Village tour', 'Pidurangala Rock hike']],
                ['Sigiriya & Dambulla', 'Climb Lion Rock, then visit the golden cave temples.', ['Sigiriya Lion Rock', 'Dambulla Cave Temple'], 'Sigiriya', self::B, 40, 1, ['Sigiriya Rock climb', 'Dambulla Cave Temple visit']],
                ['Polonnaruwa', 'Cycle through the medieval capital, evening jeep safari at Minneriya.', ['Gal Vihara', 'Minneriya National Park'], 'Sigiriya', self::B, 110, 2.5, ['Ancient city cycling tour', 'The Gathering at Minneriya']],
                ['Kandy', 'Spice garden, Temple of the Tooth and a cultural dance show.', ['Temple of the Sacred Tooth Relic', 'Kandy Market'], null, self::B, 95, 2.5, ['Spice garden visit', 'Temple of the Tooth puja ceremony', 'Kandyan cultural dance show']],
            ]],
        'hill-country-tea-trails' => ['Hill Country & Tea Trails', 'hill-country-tea-tours', 540, true, 'photo-1578519050142-afb511e518de',
            'Ride the famous blue train through tea estates to Ella’s waterfalls and viewpoints.',
            ['Scenic train ride Kandy → Ella', 'Tea factory tour & tasting', 'Nine Arch Bridge & Little Adam’s Peak', 'Ravana Falls', 'Gregory Lake, Nuwara Eliya'],
            ['Private vehicle & chauffeur-guide', 'Reserved train tickets', '3 nights accommodation with breakfast', 'Tea factory entrance'],
            [
                ['Kandy → Nuwara Eliya', 'Botanical gardens, tea factory and town tour.', ['Royal Botanic Gardens, Peradeniya', 'Gregory Lake'], 'Nuwara Eliya', self::B, 80, 2.5, ['Tea factory tour & tasting']],
                ['Nuwara Eliya → Ella', 'The iconic scenic train ride through the highlands.', [], 'Ella', self::B, 60, 3, ['Scenic train ride']],
                ['Ella', 'Sunrise hike, Nine Arch Bridge and Ravana Falls.', ['Little Adam’s Peak', 'Nine Arch Bridge', 'Ravana Falls'], 'Ella', self::B, 15, 0.5, ['Little Adam’s Peak sunrise hike', 'Nine Arch Bridge visit', 'Waterfall visit']],
                ['Departure', 'Drive to the south coast or Colombo.', [], null, self::B, 210, 4.5, []],
            ]],
        'southern-coast-escape' => ['Southern Coast Escape', 'beach-tours', 620, true, 'photo-1580910527739-556eb89f9d65',
            'Galle Fort, turtle hatcheries, whale watching and lazy days on golden beaches.',
            ['Galle Dutch Fort walking tour', 'Whale & dolphin watching in Mirissa', 'Coconut Tree Hill sunset', 'Madu River boat safari', 'Turtle hatchery visit'],
            ['Private vehicle & chauffeur-guide', '4 nights beach hotel with breakfast', 'Whale watching boat ticket', 'All transfers'],
            [
                ['Colombo → Bentota', 'Madu River safari and turtle hatchery.', ['Bentota Beach'], 'Bentota', self::B, 90, 2, ['Madu River boat safari', 'Turtle hatchery visit']],
                ['Galle', 'Explore the UNESCO fort, ramparts and boutiques.', ['Galle Fort', 'Unawatuna Beach'], 'Galle', self::B, 65, 1.5, ['Galle Fort walking tour', 'Stilt fishermen visit']],
                ['Mirissa', 'Morning whale watching, afternoon on the beach.', ['Mirissa Beach', 'Coconut Tree Hill'], 'Mirissa', self::B, 40, 1, ['Whale watching']],
                ['Free day', 'Snorkelling, surfing lessons or a spa day.', [], 'Mirissa', self::B, null, null, ['Snorkelling', 'Surfing lesson', 'Ayurveda treatment']],
                ['Departure', 'Transfer to the airport via the expressway.', [], null, self::B, 180, 3, []],
            ]],
        'wild-sri-lanka-safari' => ['Wild Sri Lanka Safari', 'wildlife-safari-tours', 760, true, 'photo-1549366021-9f761d450615',
            'Leopards in Yala, elephants in Udawalawe and birdlife in wild wetlands.',
            ['Two jeep safaris in Yala National Park', 'Udawalawe elephant safari', 'Elephant Transit Home feeding', 'Bundala bird sanctuary', 'Luxury tented camp night'],
            ['Private safari jeeps & trackers', 'Park entrance fees', '3 nights (1 night glamping)', 'All meals on safari days'],
            [
                ['Arrival → Udawalawe', 'Afternoon elephant safari.', ['Udawalawe National Park'], 'Udawalawe', ['dinner'], 190, 4, ['Elephant safari']],
                ['Udawalawe → Yala', 'Elephant Transit Home, evening at the camp.', [], 'Tissamaharama', ['breakfast', 'lunch', 'dinner'], 90, 2, ['Elephant Transit Home visit']],
                ['Yala', 'Full-day leopard safari with packed lunch.', ['Yala National Park'], 'Tissamaharama', ['breakfast', 'lunch', 'dinner'], 40, 1, ['Leopard safari in Yala']],
                ['Departure', 'Morning birding in Bundala, then transfer.', [], null, self::B, 260, 4.5, ['Bird watching']],
            ]],
        'colombo-city-explorer' => ['Colombo City Explorer', 'day-tours', 95, false, 'photo-1546708973-b339540b5162',
            'Colonial landmarks, bustling markets, temples and the best street food in the capital.',
            ['Gangaramaya Temple', 'Pettah market tuk-tuk ride', 'Galle Face Green sunset', 'Independence Square', 'Street-food tasting'],
            ['Private vehicle & guide', 'Tuk-tuk ride', 'Street-food tasting', 'Bottled water'],
            [
                ['Colombo', 'Full-day guided city tour with pick-up from your hotel.', ['Gangaramaya Temple', 'Pettah Market'], null, ['lunch'], 30, 1.5, ['Tuk-tuk city tour', 'Street-food tasting']],
            ]],
        'adventure-sri-lanka' => ['Adventure Sri Lanka', 'adventure-tours', 880, false, 'photo-1581791534721-e599df4417f7',
            'White-water rafting, jungle hikes, waterfalls and canyoning for the bold traveller.',
            ['White-water rafting in Kitulgala', 'Canyoning & waterfall jumps', 'Adam’s Peak night climb', 'Knuckles mountain trek', 'Zip-lining in Ella'],
            ['Private vehicle & guide', 'All adventure activities with instructors', '5 nights accommodation', 'Safety gear'],
            [
                ['Colombo → Kitulgala', 'Rafting the Kelani River.', ['Kelani River Rafting'], 'Kitulgala', self::B, 90, 2.5, ['White-water rafting']],
                ['Kitulgala', 'Canyoning and a jungle trek.', [], 'Kitulgala', self::B, null, null, ['Canyoning', 'Jungle trek']],
                ['Adam’s Peak', 'Night climb for sunrise at the summit.', [], 'Nuwara Eliya', self::B, 60, 2, ['Adam’s Peak night climb']],
                ['Knuckles Range', 'Guided trek through cloud forest.', [], 'Kandy', self::B, 110, 3, ['Knuckles mountain trek']],
                ['Ella', 'Flying Ravana zip-line.', ['Nine Arch Bridge'], 'Ella', self::B, 140, 4, ['Zip-lining']],
                ['Departure', 'Transfer to Colombo.', [], null, self::B, 220, 5, []],
            ]],
    ];

    public function run(): void
    {
        $categories = TourCategory::pluck('id', 'slug');
        $points = Destination::pluck('id', 'name');
        $cities = City::pluck('id', 'name');
        $activityIds = Activity::pluck('id', 'name');
        $i = 0;

        foreach (self::TOURS as $slug => [$title, $category, $price, $featured, $photo, $summary, $highlights, $inclusions, $days]) {
            if (! isset($categories[$category])) {
                continue;
            }
            if ($existing = Tour::withTrashed()->where('slug', $slug)->first()) {
                DemoImages::attach($existing, 'cover', $photo, 1200); // re-run with SEED_IMAGES=true adds missing photos

                continue;
            }

            $tour = Tour::create([
                'tour_category_id' => $categories[$category],
                'title' => $title,
                'slug' => $slug,
                'summary' => $summary,
                'highlights' => $highlights,
                'inclusions' => $inclusions,
                'exclusions' => ['International flights', 'Visa fees', 'Tips & personal expenses'],
                'pace' => $category === 'adventure-tours' ? TourPace::Active : TourPace::Moderate,
                'group_min' => 1,
                'group_max' => 12,
                'start_point' => 'Colombo Airport (BIA)',
                'end_point' => 'Colombo Airport (BIA)',
                'best_months' => [1, 2, 3, 4, 12],
                'status' => TourStatus::Published,
                'is_featured' => $featured,
                'sort_order' => ++$i,
            ]);

            foreach ($days as $n => [$dayTitle, $details, $names, $overnight, $meals, $km, $hours, $activities]) {
                $day = $tour->days()->create([
                    'day_number' => $n + 1,
                    'title' => $dayTitle,
                    'description' => $details,
                    'overnight_city_id' => $overnight ? ($cities[$overnight] ?? null) : null,
                    'meals' => $meals,
                    'distance_km' => $km,
                    'drive_hours' => $hours,
                ]);
                $ids = collect($names)->map(fn (string $name) => $points[$name] ?? null)->filter()->values();
                $day->destinations()->sync($ids->mapWithKeys(fn (int $id, int $o) => [$id => ['sort_order' => $o + 1]])->all());
                $acts = collect($activities)->map(fn (string $name) => $activityIds[$name] ?? null)->filter()->values();
                $day->activities()->sync($acts->mapWithKeys(fn (int $id, int $o) => [$id => ['sort_order' => $o + 1]])->all());
            }

            foreach ([[1, 2, 1.4, 'Car'], [3, 5, 1.15, 'Van'], [6, null, 1.0, 'Mini coach']] as [$from, $to, $factor, $vehicle]) {
                $tour->prices()->create(['pax_from' => $from, 'pax_to' => $to, 'vehicle' => $vehicle, 'price_per_person' => round($price * $factor)]);
            }

            DemoImages::attach($tour, 'cover', $photo, 1200);
        }
    }
}
