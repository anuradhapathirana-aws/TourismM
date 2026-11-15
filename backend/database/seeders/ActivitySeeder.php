<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Activity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Starter library of Sri Lankan tour activities (runs in production too; the admin edits them afterwards).
 * Idempotent: an activity that already exists by slug or name (any case) is left untouched.
 */
final class ActivitySeeder extends Seeder
{
    /** Grouped only for readability; sort order follows this list. name => description */
    public const ACTIVITIES = [
        // Wildlife & nature
        'Jeep safari' => 'Private 4x4 game drive with a park tracker through one of Sri Lanka’s national parks.',
        'Leopard safari in Yala' => 'Early-morning game drive in Yala National Park, home to one of the world’s highest leopard densities.',
        'Elephant safari' => 'Afternoon drive among the wild elephant herds of Udawalawe, Minneriya or Kaudulla.',
        'The Gathering at Minneriya' => 'Watch hundreds of wild elephants meet at the Minneriya reservoir (best July to October).',
        'Wilpattu safari' => 'Quiet safari among the natural lakes (villus) of Sri Lanka’s largest national park.',
        'Elephant Transit Home visit' => 'See orphaned baby elephants fed before they are released back into the wild.',
        'Whale watching' => 'Boat trip from Mirissa or Trincomalee to see blue whales, sperm whales and dolphins.',
        'Dolphin watching' => 'Morning boat ride off Kalpitiya to see large pods of spinner dolphins.',
        'Bird watching' => 'Guided birding walk or drive to spot endemic and migratory birds.',
        'Turtle hatchery visit' => 'Learn about sea-turtle conservation and see hatchlings at a coastal hatchery.',
        'Turtle watching at night' => 'Watch sea turtles come ashore to nest at Rekawa beach (guided, no flash).',
        'Sinharaja rainforest trek' => 'Guided walk through the UNESCO-listed Sinharaja Forest Reserve, rich in endemic species.',
        'Horton Plains & World’s End hike' => 'Early hike across the highland plateau to the World’s End cliff and Baker’s Falls.',

        // Culture & heritage
        'Sigiriya Rock climb' => 'Climb the 5th-century Lion Rock fortress past the frescoes and the Lion’s Paw terrace.',
        'Pidurangala Rock hike' => 'Short, steep hike for a sunrise or sunset view of Sigiriya Rock.',
        'Dambulla Cave Temple visit' => 'Explore five painted cave shrines filled with ancient Buddha statues.',
        'Ancient city cycling tour' => 'Cycle between the ruins of Polonnaruwa or Anuradhapura with your guide.',
        'Temple of the Tooth puja ceremony' => 'Attend the evening drumming ceremony at Kandy’s Temple of the Sacred Tooth Relic.',
        'Kandyan cultural dance show' => 'Traditional drummers, dancers and fire-walkers in Kandy.',
        'Village tour' => 'Bullock-cart ride, lake canoe and a home-cooked lunch with a farming family.',
        'Sri Lankan cooking class' => 'Cook rice and curry with a local family, from market to table.',
        'Spice garden visit' => 'Guided walk through a spice garden with cinnamon, cardamom and pepper.',
        'Galle Fort walking tour' => 'Walk the ramparts, lanes and churches of the UNESCO-listed Dutch fort.',
        'Stilt fishermen visit' => 'See the traditional stilt fishermen of Koggala and Ahangama.',
        'Mask carving workshop' => 'Visit a traditional mask maker in Ambalangoda and learn the craft.',
        'Batik workshop' => 'Watch and try the art of Sri Lankan batik painting.',
        'Gem museum visit' => 'Learn how sapphires and other gems are mined and cut in Ratnapura.',
        'Meditation at a monastery' => 'Guided meditation session with a Buddhist monk.',

        // Hill country & tea
        'Scenic train ride' => 'The famous blue train through tea estates and misty mountains (Kandy – Nuwara Eliya – Ella).',
        'Tea factory tour & tasting' => 'See how tea is withered, rolled and dried, then taste fresh Ceylon tea.',
        'Tea plucking experience' => 'Join the tea pluckers on an estate and learn the “two leaves and a bud” technique.',
        'Nine Arch Bridge visit' => 'Watch the train cross Ella’s colonial-era stone viaduct.',
        'Little Adam’s Peak sunrise hike' => 'Easy hike in Ella with views across the valley at sunrise.',
        'Adam’s Peak night climb' => 'Overnight pilgrimage climb of Sri Pada to watch sunrise from the summit.',
        'Knuckles mountain trek' => 'Guided trek through the cloud forests and villages of the Knuckles Range.',
        'Waterfall visit' => 'Stop at one of the island’s waterfalls, such as Ravana, Diyaluma or Bambarakanda.',

        // Adventure
        'White-water rafting' => 'Grade 2–3 rafting on the Kelani River at Kitulgala with trained instructors.',
        'Canyoning' => 'Slide, jump and abseil down natural rock pools and waterfalls.',
        'Zip-lining' => 'Fly over the jungle on the Flying Ravana zip-line in Ella.',
        'Hot-air ballooning' => 'Sunrise balloon flight over the Cultural Triangle (seasonal).',
        'Jungle trek' => 'Guided walk through tropical forest with a naturalist.',
        'Kayaking' => 'Paddle a lagoon, lake or mangrove channel.',

        // Beach & water
        'Snorkelling' => 'Snorkel over coral reefs and with turtles at Hikkaduwa, Pigeon Island or Polhena.',
        'Scuba diving' => 'Guided dives for beginners and certified divers, including wreck dives.',
        'Surfing lesson' => 'Beginner-friendly lesson with a local surf coach in Weligama or Arugam Bay.',
        'Kitesurfing' => 'Lessons and sessions on the Kalpitiya lagoon (seasonal winds).',
        'Madu River boat safari' => 'Boat ride through mangroves and cinnamon islands near Bentota.',
        'Sunset catamaran cruise' => 'Evening sail along the coast with drinks and snacks.',
        'Deep-sea fishing' => 'Half-day fishing trip with a local skipper.',

        // City & food
        'Tuk-tuk city tour' => 'See Colombo’s landmarks, markets and temples from a tuk-tuk.',
        'Street-food tasting' => 'Try kottu, hoppers, isso vadai and local sweets with your guide.',
        'Colombo shopping tour' => 'Boutiques, handloom and tea shops, and the best places for souvenirs.',

        // Wellness
        'Ayurveda treatment' => 'Traditional Ayurveda massage or treatment with a qualified practitioner.',
        'Yoga session' => 'Guided yoga class, by the beach or in the hills.',
    ];

    public function run(): void
    {
        $i = 0;
        foreach (self::ACTIVITIES as $name => $description) {
            $i++;
            $existing = Activity::withTrashed()
                ->where('slug', Str::slug($name))
                ->orWhereRaw('lower(name) = ?', [mb_strtolower($name)])
                ->first();

            if (! $existing) {
                Activity::create(['name' => $name, 'description' => $description, 'sort_order' => $i]);
            } elseif (blank($existing->description)) {
                // e.g. moved from the old free-text list: fill the gap, never overwrite the admin's text
                $existing->update(['description' => $description, 'sort_order' => $existing->sort_order ?: $i]);
            }
        }
    }
}
