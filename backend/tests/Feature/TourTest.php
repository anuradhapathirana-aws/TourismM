<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DestinationStatus;
use App\Enums\TourStatus;
use App\Filament\Resources\TourResource\Pages\CreateTour;
use App\Filament\Resources\TourResource\Pages\EditTour;
use App\Filament\Resources\TourResource\Pages\ListTours;
use App\Models\Activity;
use App\Models\Destination;
use App\Models\Tour;
use App\Models\TourCategory;
use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Tours: plan-a-tour form (both validation layers), ordered points per day, prices, duplicate, public API. */
final class TourTest extends TestCase
{
    use RefreshDatabase;

    private TourCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->category = TourCategory::factory()->create(['name' => 'Cultural Tours']);
    }

    private function actingAsEditor(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Permissions::CONTENT_EDITOR);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    /** @return array<string, mixed> */
    private function validTour(array $overrides = []): array
    {
        return [
            'tour_category_id' => $this->category->id,
            'title' => '5 Days Cultural Triangle',
            'slug' => '5-days-cultural-triangle',
            'summary' => 'Ancient kingdoms, cave temples and the Lion Rock.',
            'pace' => 'moderate',
            'group_min' => 1,
            'status' => 'draft',
            'days' => [['title' => 'Arrival', 'destination_ids' => []]],
            'prices' => [],
            ...$overrides,
        ];
    }

    public function test_server_validates_the_tour_days_and_price_table(): void
    {
        $this->actingAsEditor();
        $undo = Repeater::fake();

        Livewire::test(CreateTour::class)
            ->fillForm(['tour_category_id' => null, 'title' => 'AB', 'summary' => 'short', 'group_min' => 4, 'group_max' => 2, 'days' => [], 'prices' => []])
            ->call('create')
            ->assertHasFormErrors(['tour_category_id' => 'required', 'title' => 'min', 'summary' => 'min', 'group_max' => 'gte', 'days']);

        Livewire::test(CreateTour::class)
            ->fillForm($this->validTour(['days' => [['title' => '', 'distance_km' => 5000, 'drive_hours' => 30, 'destination_ids' => [999999], 'activity_ids' => [999999]]]]))
            ->call('create')
            ->assertHasFormErrors(['days.0.title' => 'required', 'days.0.distance_km', 'days.0.drive_hours', 'days.0.destination_ids.0', 'days.0.activity_ids.0']);

        // Publishing needs a price row (and a cover image)
        Livewire::test(CreateTour::class)
            ->fillForm($this->validTour(['status' => 'published']))
            ->call('create')
            ->assertHasFormErrors(['prices', 'cover' => 'required']);

        // Overlapping group sizes (1–3 and 2–4 both cover 2 and 3 travellers)
        Livewire::test(CreateTour::class)
            ->fillForm($this->validTour(['prices' => [
                ['pax_from' => 1, 'pax_to' => 3, 'price_per_person' => 900],
                ['pax_from' => 2, 'pax_to' => 4, 'price_per_person' => 700],
            ]]))
            ->call('create')
            ->assertHasFormErrors(['prices']);

        $undo();
    }

    public function test_admin_plans_a_tour_with_points_in_visit_order_and_a_price_table(): void
    {
        $this->actingAsEditor();
        $undo = Repeater::fake();
        [$sigiriya, $dambulla, $kandy] = Destination::factory()->count(3)->create();
        [$village, $cycling] = Activity::factory()->count(2)->create();

        Livewire::test(CreateTour::class)
            ->fillForm($this->validTour([
                'highlights' => ['Lion Rock <b>sunrise</b>', 'Cave temples'],
                'days' => [
                    ['title' => 'Arrival', 'destination_ids' => [], 'meals' => ['dinner']],
                    ['title' => 'Sigiriya & Dambulla', 'destination_ids' => [$dambulla->id, $sigiriya->id], 'meals' => ['breakfast'], 'activity_ids' => [$cycling->id, $village->id], 'distance_km' => 40, 'drive_hours' => 1.5],
                    ['title' => 'Kandy', 'destination_ids' => [$kandy->id]],
                ],
                'prices' => [
                    ['pax_from' => 1, 'pax_to' => 2, 'vehicle' => 'Car', 'price_per_person' => 900],
                    ['pax_from' => 3, 'pax_to' => null, 'vehicle' => 'Van', 'price_per_person' => 650],
                ],
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $tour = Tour::where('slug', '5-days-cultural-triangle')->firstOrFail();
        $this->assertSame(['Lion Rock sunrise', 'Cave temples'], $tour->highlights);
        $this->assertSame(['Arrival', 'Sigiriya & Dambulla', 'Kandy'], $tour->days->pluck('title')->all());
        $this->assertSame([1, 2, 3], $tour->days->pluck('day_number')->all());
        $this->assertSame([$dambulla->id, $sigiriya->id], $tour->days[1]->destinations->pluck('id')->all()); // visit order kept
        $this->assertSame(['breakfast'], $tour->days[1]->meals);
        $this->assertSame([$cycling->id, $village->id], $tour->days[1]->activities->pluck('id')->all()); // picked order kept
        $this->assertSame('3 Days / 2 Nights', Tour::durationLabel($tour->days->count()));
        $this->assertEquals(650, $tour->prices()->min('price_per_person'));

        // Editing loads the ordered points back into the form and saves a new order
        $dayKey = 'record-'.$tour->days[1]->id;
        Livewire::test(EditTour::class, ['record' => $tour->getRouteKey()])
            ->assertFormSet(["days.{$dayKey}.destination_ids" => [$dambulla->id, $sigiriya->id], "days.{$dayKey}.activity_ids" => [$cycling->id, $village->id]])
            ->fillForm(["days.{$dayKey}.destination_ids" => [$sigiriya->id, $dambulla->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([$sigiriya->id, $dambulla->id], $tour->days[1]->fresh()->destinations->pluck('id')->all());

        $undo();
    }

    public function test_duplicate_copies_days_points_and_prices_as_a_draft(): void
    {
        $this->actingAsEditor();
        $points = Destination::factory()->count(2)->create()->pluck('id')->all();
        $tour = Tour::factory()->published()->withDays([[$points[1], $points[0]], []])->create(['title' => '7 Days Island Tour', 'is_featured' => true]);

        $activity = Activity::factory()->create();
        $tour->days()->first()->activities()->attach($activity->id, ['sort_order' => 1]);

        Livewire::test(ListTours::class)->callTableAction('duplicate', $tour);

        $copy = Tour::where('title', '7 Days Island Tour (copy)')->firstOrFail();
        $this->assertSame(TourStatus::Draft, $copy->status);
        $this->assertFalse($copy->is_featured);
        $this->assertSame(2, $copy->days()->count());
        $this->assertSame([$points[1], $points[0]], $copy->days()->first()->destinations->pluck('id')->all());
        $this->assertSame(2, $copy->prices()->count());
        $this->assertSame([$activity->id], $copy->days()->first()->activities->pluck('id')->all());
        $this->assertSame(2, $tour->days()->count()); // original untouched
    }

    public function test_publish_action_requires_cover_days_and_prices(): void
    {
        $this->actingAsEditor();
        $tour = Tour::factory()->create();

        Livewire::test(ListTours::class)->callTableAction('publish', $tour);

        $this->assertSame(TourStatus::Draft, $tour->fresh()->status);
    }

    public function test_admin_pages_render_and_tours_menu_comes_first(): void
    {
        $this->actingAsEditor();
        $tour = Tour::factory()->withDays([[]])->create();
        $admin = '/'.config('admin.path');

        $this->get("{$admin}/tours/create")->assertOk()->assertSee('lg-itinerary', false)->assertSee('Price per person by group size');
        $this->get("{$admin}/tours/{$tour->slug}/edit")->assertOk();
        $this->get("{$admin}/tours")->assertOk()->assertSeeInOrder(['Tours', 'Tour categories', 'Cities', 'Destination points']);
    }

    public function test_public_api_matches_the_frontend_contract(): void
    {
        $published = Destination::factory()->create(['name' => 'Sigiriya Lion Rock', 'status' => DestinationStatus::Published]);
        $draft = Destination::factory()->create(['name' => 'Secret Draft', 'status' => DestinationStatus::Draft]);

        Tour::factory()->published()->withDays([[$published->id, $draft->id], []], [[1, 2, 900], [3, null, 650]])
            ->create(['tour_category_id' => $this->category->id, 'title' => 'Cultural Triangle', 'slug' => 'cultural-triangle', 'is_featured' => true]);
        Tour::factory()->create(['title' => 'Draft Tour', 'slug' => 'draft-tour']);
        Tour::factory()->published()->for(TourCategory::factory()->state(['is_active' => false]), 'category')->create(['slug' => 'hidden-category']);

        $this->getJson('/api/v1/tours')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'Cultural Tours')
            ->assertJsonPath('data.0.duration', '2 Days / 1 Night')
            ->assertJsonPath('data.0.price', 650)
            ->assertJsonPath('data.0.featured', true)
            ->assertJsonStructure(['data' => [['id', 'slug', 'title', 'category', 'duration', 'price', 'featured', 'image', 'summary', 'highlights', 'itinerary' => [['title', 'details']], 'inclusions']]]);

        $this->getJson('/api/v1/tours?category=cultural-tours')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/tours?category=beach-tours')->assertOk()->assertJsonCount(0, 'data');

        $this->getJson('/api/v1/tours/cultural-triangle')->assertOk()
            ->assertJsonPath('data.prices.0.pax', '1–2')
            ->assertJsonPath('data.prices.1.pax', '3+')
            ->assertJsonPath('data.itinerary.0.day', 1)
            ->assertJsonCount(1, 'data.itinerary.0.destinations') // draft point hidden
            ->assertJsonPath('data.itinerary.0.destinations.0.name', 'Sigiriya Lion Rock');

        $this->getJson('/api/v1/tours/draft-tour')->assertNotFound();
        $this->getJson('/api/v1/tours/hidden-category')->assertNotFound();

        $this->getJson('/api/v1/tour-categories')->assertOk()
            ->assertJsonPath('data.0.toursCount', 1)
            ->assertJsonPath('data.0.label', 'Cultural');
    }

    public function test_public_tour_list_is_safe_and_cacheable(): void
    {
        Tour::factory()->published()->withDays([[]])->create(['tour_category_id' => $this->category->id, 'slug' => 'featured-one', 'is_featured' => true]);
        Tour::factory()->published()->withDays([[]])->create(['tour_category_id' => $this->category->id, 'slug' => 'normal-one']);
        Tour::factory()->withDays([[]])->create(['tour_category_id' => $this->category->id, 'slug' => 'draft-one', 'is_featured' => true]);

        $list = $this->getJson('/api/v1/tours')->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.slug', 'featured-one') // featured first
            ->assertJsonPath('data.0.categoryLabel', 'Cultural')
            ->assertJsonMissingPath('data.0.status')
            ->assertJsonMissingPath('data.0.metaTitle')
            ->assertHeader('ETag');
        $this->assertStringContainsString('public', (string) $list->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=60', (string) $list->headers->get('Cache-Control'));

        // Same content + If-None-Match → 304 without a body
        $this->getJson('/api/v1/tours', ['If-None-Match' => $list->headers->get('ETag')])->assertStatus(304)->assertContent('');

        $this->getJson('/api/v1/tours?featured=1')->assertOk()->assertJsonCount(1, 'data');

        // Bad filters are rejected (422 JSON), errors are never cached
        $bad = $this->getJson('/api/v1/tours?category=<script>')->assertStatus(422)->assertJsonValidationErrors('category');
        $this->assertStringNotContainsString('public', (string) $bad->headers->get('Cache-Control'));
        $this->getJson('/api/v1/tours?featured=maybe')->assertStatus(422);
        $this->getJson('/api/v1/tours/Not_A_Slug')->assertNotFound();

        // Read-only: writes are not routed
        $this->postJson('/api/v1/tours', ['title' => 'x'])->assertStatus(405);
        $this->deleteJson('/api/v1/tours/featured-one')->assertStatus(405);
    }

    public function test_category_label_drops_the_word_tours(): void
    {
        $this->assertSame('Cultural & Heritage', TourCategory::factory()->make(['name' => 'Cultural & Heritage Tours'])->label());
        $this->assertSame('Day', TourCategory::factory()->make(['name' => 'Day tour'])->label());
        $this->assertSame('Ayurveda', TourCategory::factory()->make(['name' => 'Ayurveda'])->label());
        $this->assertSame('Tours', TourCategory::factory()->make(['name' => 'Tours'])->label());
    }
}
