<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DestinationStatus;
use App\Models\City;
use App\Models\Destination;
use App\Models\District;
use App\Models\PlaceType;
use App\Models\Theme;
use Illuminate\Database\Seeder;

/**
 * Sample cities and their destination points (placeholder content; replace in the admin).
 * Point format: [name, place type, lat, lng, theme slugs, summary, featured photo id|null]
 */
final class DemoLocationSeeder extends Seeder
{
    public const CITIES = [
        ['Anuradhapura', 'Anuradhapura', 8.3114, 80.4037, 'Sri Lanka’s first ancient capital: giant dagobas and sacred Bodhi tree.', [
            ['Ruwanwelisaya', 'Temple', 8.3500, 80.3964, ['culture'], 'Brilliant white 2nd-century BC stupa built by King Dutugemunu.', null],
            ['Jaya Sri Maha Bodhi', 'Temple', 8.3446, 80.3967, ['culture'], 'The oldest documented planted tree in the world, grown from Buddha’s Bodhi tree.', null],
            ['Isurumuniya', 'Temple', 8.3360, 80.3887, ['culture', 'lesser-travelled'], 'Rock temple famous for the carved “Isurumuniya Lovers”.', null],
        ]],
        ['Ella', 'Badulla', 6.8667, 81.0466, 'Laid-back hill-country village with waterfalls, tea and epic views.', [
            ['Nine Arch Bridge', 'Viewpoint', 6.8768, 81.0608, ['adventure'], 'Colonial-era stone viaduct in the jungle. Catch a blue train crossing.', 'photo-1566296314736-6eaac1ca0cb9'],
            ['Ravana Falls', 'Waterfall', 6.8411, 81.0546, ['adventure', 'wildlife'], 'A 25 m cascade linked to the legend of King Ravana.', null],
            ['Little Adam’s Peak', 'Mountain & Hike', 6.8667, 81.0631, ['adventure'], 'Easy sunrise hike through tea fields to a 360° panorama.', null],
        ]],
        ['Kandy', 'Kandy', 7.2906, 80.6337, 'The hill capital around a lake, home of the Temple of the Tooth.', [
            ['Temple of the Sacred Tooth Relic', 'Temple', 7.2936, 80.6413, ['culture'], 'Sri Lanka’s holiest shrine, housing a tooth relic of the Buddha.', 'photo-1665849050332-8d5d7e59afb6'],
            ['Royal Botanic Gardens, Peradeniya', 'Garden', 7.2690, 80.5966, ['wildlife'], '60 hectares of orchids, giant bamboo and royal palm avenues.', null],
            ['Kandy Market', 'Food & Market', 7.2930, 80.6350, ['gastronomy'], 'Colourful spice, fruit and sweet stalls in the heart of town.', null],
        ]],
        ['Sigiriya', 'Matale', 7.9500, 80.7600, 'Village below the iconic Lion Rock fortress.', [
            ['Sigiriya Lion Rock', 'Heritage Site', 7.9570, 80.7603, ['culture', 'adventure'], 'The 5th-century rock fortress rising 200 m above the jungle.', 'photo-1612862862126-865765df2ded'],
            ['Pidurangala Rock', 'Mountain & Hike', 7.9660, 80.7593, ['adventure', 'lesser-travelled'], 'Short scramble to the best view of Sigiriya at sunrise.', null],
        ]],
        ['Dambulla', 'Matale', 7.8600, 80.6517, 'Market town with Sri Lanka’s largest cave-temple complex.', [
            ['Dambulla Cave Temple', 'Temple', 7.8567, 80.6492, ['culture'], 'Five caves filled with 150 Buddha statues and painted ceilings.', null],
        ]],
        ['Galle', 'Galle', 6.0535, 80.2210, 'Dutch-colonial fort town on the south coast.', [
            ['Galle Fort', 'Heritage Site', 6.0269, 80.2170, ['culture', 'gastronomy'], 'UNESCO-listed ramparts, lighthouse and boutique cafés.', null],
            ['Unawatuna Beach', 'Beach', 6.0098, 80.2495, ['beaches'], 'Sheltered bay with calm water for swimming and snorkelling.', null],
        ]],
        ['Mirissa', 'Matara', 5.9483, 80.4716, 'Palm-fringed beach town and whale-watching hub.', [
            ['Mirissa Beach', 'Beach', 5.9467, 80.4686, ['beaches'], 'Golden crescent of sand, famous sunsets and blue whale trips.', 'photo-1580910527739-556eb89f9d65'],
            ['Coconut Tree Hill', 'Viewpoint', 5.9412, 80.4583, ['beaches'], 'A palm-covered headland over the Indian Ocean.', null],
        ]],
        ['Nuwara Eliya', 'Nuwara Eliya', 6.9497, 80.7891, '“Little England”: cool climate and emerald tea estates.', [
            ['Gregory Lake', 'Lake & River', 6.9580, 80.7700, ['wildlife', 'gastronomy'], 'Colonial lakeside promenade with boating and tea-country views.', 'photo-1544015759-237f87d55ef3'],
            ['Horton Plains National Park', 'National Park', 6.8020, 80.8070, ['wildlife', 'adventure'], 'Misty plateau trek to the sheer drop of World’s End.', null],
        ]],
        ['Tissamaharama', 'Hambantota', 6.2833, 81.2833, 'Gateway town to Yala National Park.', [
            ['Yala National Park', 'National Park', 6.3725, 81.5185, ['wildlife'], 'The best place on earth to spot wild leopards.', 'photo-1456926631375-92c8ce872def'],
        ]],
        ['Udawalawe', 'Ratnapura', 6.4260, 80.8880, 'Village on the edge of elephant country.', [
            ['Udawalawe National Park', 'National Park', 6.4740, 80.8987, ['wildlife'], 'Herds of wild elephants on open grasslands around the reservoir.', 'photo-1564760055775-d63b17a55c44'],
        ]],
        ['Trincomalee', 'Trincomalee', 8.5874, 81.2152, 'Natural harbour city with east-coast beaches and reefs.', [
            ['Nilaveli Beach', 'Beach', 8.6935, 81.1990, ['beaches', 'adventure'], 'Crystal water and boat trips to Pigeon Island coral reef.', 'photo-1544551763-46a013bb70d5'],
            ['Koneswaram Temple', 'Temple', 8.5815, 81.2450, ['culture'], 'Clifftop Hindu temple on Swami Rock above the ocean.', null],
        ]],
        ['Arugam Bay', 'Ampara', 6.8400, 81.8350, 'Relaxed surf village on the far east coast.', [
            ['Arugam Bay Main Point', 'Beach', 6.8380, 81.8380, ['beaches', 'adventure', 'lesser-travelled'], 'World-class right-hand point break (best May–September).', null],
        ]],
        ['Polonnaruwa', 'Polonnaruwa', 7.9403, 81.0188, 'Medieval royal capital of palaces and rock sculptures.', [
            ['Gal Vihara', 'Heritage Site', 7.9659, 81.0045, ['culture'], 'Four colossal Buddha figures carved into one granite face.', null],
        ]],
        ['Habarana', 'Anuradhapura', 8.0350, 80.7490, 'Safari base in the Cultural Triangle.', [
            ['Minneriya National Park', 'National Park', 8.0400, 80.9000, ['wildlife'], 'Home of “The Gathering”: hundreds of elephants in the dry season.', null],
        ]],
        ['Jaffna', 'Jaffna', 9.6615, 80.0255, 'Tamil cultural capital of the far north.', [
            ['Nallur Kandaswamy Kovil', 'Temple', 9.6745, 80.0295, ['culture', 'lesser-travelled'], 'Golden-towered Hindu temple and heart of the Nallur festival.', null],
            ['Jaffna Market', 'Food & Market', 9.6620, 80.0130, ['gastronomy', 'lesser-travelled'], 'Taste Jaffna crab curry, odiyal kool and palmyra sweets.', null],
        ]],
        ['Kitulgala', 'Kegalle', 6.9925, 80.4186, 'Rainforest village on the Kelani river.', [
            ['Kelani River Rafting', 'Adventure Activity', 6.9890, 80.4180, ['adventure'], 'Grade 2–3 white-water rafting through rainforest gorges.', null],
        ]],
        ['Bentota', 'Galle', 6.4250, 79.9950, 'Resort town of long beaches and river safaris.', [
            ['Bentota Beach', 'Beach', 6.4210, 79.9980, ['beaches'], 'Long golden beach with water sports and the Madu river nearby.', null],
        ]],
        ['Colombo', 'Colombo', 6.9271, 79.8612, 'The busy commercial capital by the sea.', [
            ['Gangaramaya Temple', 'Temple', 6.9166, 79.8565, ['culture'], 'Eclectic temple-museum beside Beira Lake.', null],
            ['Pettah Market', 'Food & Market', 6.9360, 79.8500, ['gastronomy'], 'Bustling bazaar streets of spices, street food and textiles.', null],
        ]],
    ];

    public function run(): void
    {
        $types = PlaceType::pluck('id', 'name');
        $themes = Theme::pluck('id', 'slug');
        $districts = District::pluck('id', 'name');

        foreach (self::CITIES as $c => [$cityName, $district, $lat, $lng, $summary, $points]) {
            $city = City::firstOrCreate(['name' => $cityName, 'district_id' => $districts[$district]], [
                'summary' => $summary,
                'latitude' => $lat,
                'longitude' => $lng,
                'sort_order' => $c + 1,
            ]);

            foreach ($points as $p => [$name, $type, $pLat, $pLng, $themeSlugs, $pSummary, $photo]) {
                $destination = Destination::firstOrCreate(['city_id' => $city->id, 'name' => $name], [
                    'place_type_id' => $types[$type],
                    'summary' => $pSummary,
                    'latitude' => $pLat,
                    'longitude' => $pLng,
                    'status' => DestinationStatus::Published,
                    'is_featured' => $photo !== null,
                    'sort_order' => $p + 1,
                ]);

                $destination->themes()->syncWithoutDetaching(collect($themeSlugs)->map(fn ($s) => $themes[$s])->all());
                DemoImages::attach($destination, 'cover', $photo, 1200);
            }
        }
    }
}
