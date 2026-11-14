<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DestinationStatus;
use App\Filament\Resources\CityResource\Pages\CreateCity;
use App\Filament\Resources\CityResource\Pages\EditCity;
use App\Filament\Resources\CityResource\RelationManagers\DestinationsRelationManager;
use App\Filament\Resources\DestinationResource\Pages\CreateDestination;
use App\Models\City;
use App\Models\Destination;
use App\Models\District;
use App\Models\PlaceType;
use App\Models\Province;
use App\Models\Theme;
use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** Admin forms: every input validated server-side with the shared rule sets. */
final class DestinationAdminTest extends TestCase
{
    use RefreshDatabase;

    private City $city;

    private PlaceType $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(Permissions::CONTENT_EDITOR);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->city = City::factory()->create(['name' => 'Ella']);
        $this->type = PlaceType::factory()->create(['name' => 'Waterfall']);
    }

    /** @return array<string, mixed> */
    private function validPoint(array $overrides = []): array
    {
        return [
            'city_id' => $this->city->id,
            'place_type_id' => $this->type->id,
            'name' => 'Ravana Falls',
            'slug' => 'ravana-falls',
            'summary' => 'A 25 m waterfall near Ella linked to the Ramayana.',
            'latitude' => 6.8411,
            'longitude' => 81.0546,
            'status' => DestinationStatus::Draft->value,
            'is_featured' => false,
            ...$overrides,
        ];
    }

    public function test_admin_creates_a_destination_point_with_themes(): void
    {
        $theme = Theme::factory()->create();

        Livewire::test(CreateDestination::class)
            ->fillForm($this->validPoint(['themes' => [$theme->id]]))
            ->call('create')
            ->assertHasNoFormErrors();

        $point = Destination::where('slug', 'ravana-falls')->firstOrFail();
        $this->assertSame($this->city->id, $point->city_id);
        $this->assertSame(6.8411, $point->latitude);
        $this->assertTrue($point->themes->contains($theme));
    }

    public function test_required_fields_and_formats_are_validated(): void
    {
        Livewire::test(CreateDestination::class)
            ->fillForm([
                'city_id' => null,
                'place_type_id' => null,
                'name' => '',
                'slug' => 'Bad Slug!',
                'summary' => 'short',
                'latitude' => 'abc',
                'longitude' => null,
                'visit_duration_minutes' => 5000,
                'status' => 'archived',
            ])
            ->call('create')
            ->assertHasFormErrors([
                'city_id' => 'required',
                'place_type_id' => 'required',
                'name' => 'required',
                'slug',
                'summary',
                'latitude',
                'longitude' => 'required',
                'visit_duration_minutes',
                'status',
            ]);

        $this->assertSame(0, Destination::count());
    }

    public function test_coordinates_must_be_inside_sri_lanka(): void
    {
        Livewire::test(CreateDestination::class)
            ->fillForm($this->validPoint(['latitude' => 13.08, 'longitude' => 85.5])) // Bay of Bengal, far outside Sri Lanka
            ->call('create')
            ->assertHasFormErrors(['latitude', 'longitude']);
    }

    public function test_point_names_are_unique_per_city_only(): void
    {
        Destination::factory()->for($this->city)->create(['name' => 'Viewpoint']);

        Livewire::test(CreateDestination::class)
            ->fillForm($this->validPoint(['name' => 'Viewpoint', 'slug' => 'viewpoint-2']))
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);

        $otherCity = City::factory()->create();
        Livewire::test(CreateDestination::class)
            ->fillForm($this->validPoint(['city_id' => $otherCity->id, 'name' => 'Viewpoint', 'slug' => 'viewpoint-3']))
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_publishing_or_featuring_requires_a_cover_image(): void
    {
        Livewire::test(CreateDestination::class)
            ->fillForm($this->validPoint(['status' => DestinationStatus::Published->value]))
            ->call('create')
            ->assertHasFormErrors(['cover' => 'required']);

        Livewire::test(CreateDestination::class)
            ->fillForm($this->validPoint(['is_featured' => true]))
            ->call('create')
            ->assertHasFormErrors(['cover' => 'required']);
    }

    public function test_image_uploads_are_type_size_and_dimension_checked(): void
    {
        Storage::fake('public');

        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $tiny = UploadedFile::fake()->image('tiny.jpg', 300, 200);
        $good = UploadedFile::fake()->image('falls.jpg', 1200, 800);

        foreach ([$svg, $tiny] as $bad) {
            Livewire::test(CreateDestination::class)
                ->fillForm($this->validPoint(['cover' => [$bad]]))
                ->call('create')
                ->assertHasFormErrors(['cover']);
        }

        Livewire::test(CreateDestination::class)
            ->fillForm($this->validPoint(['status' => DestinationStatus::Published->value, 'cover' => [$good]]))
            ->call('create')
            ->assertHasNoFormErrors();

        $point = Destination::where('slug', 'ravana-falls')->firstOrFail();
        $this->assertNotNull($point->getFirstMedia('cover'));
        $this->assertTrue($point->getFirstMedia('cover')->hasGeneratedConversion('thumb'));
    }

    public function test_admin_creates_a_city_and_adds_points_inside_it(): void
    {
        $district = District::create(['name' => 'Anuradhapura', 'province_id' => Province::create(['name' => 'North Central'])->id]);

        Livewire::test(CreateCity::class)
            ->fillForm([
                'name' => 'Anuradhapura',
                'slug' => 'anuradhapura',
                'district_id' => $district->id,
                'latitude' => 8.3114,
                'longitude' => 80.4037,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $city = City::where('slug', 'anuradhapura')->firstOrFail();

        Livewire::test(DestinationsRelationManager::class, ['ownerRecord' => $city, 'pageClass' => EditCity::class])
            ->callTableAction('create', data: [
                'place_type_id' => $this->type->id,
                'name' => 'Ruwanwelisaya',
                'slug' => 'ruwanwelisaya',
                'summary' => 'Brilliant white stupa built by King Dutugemunu.',
                'latitude' => 8.35,
                'longitude' => 80.3964,
                'status' => DestinationStatus::Draft->value,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertSame(1, $city->destinations()->count());
        $this->assertSame('Ruwanwelisaya', $city->destinations()->first()->name);
    }

    public function test_city_name_must_be_unique_within_its_district(): void
    {
        Livewire::test(CreateCity::class)
            ->fillForm([
                'name' => 'Ella',
                'slug' => 'ella-2',
                'district_id' => $this->city->district_id,
                'latitude' => 6.87,
                'longitude' => 81.05,
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);
    }
}
