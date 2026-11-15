<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\ActivityResource\Pages\CreateActivity;
use App\Filament\Resources\ActivityResource\Pages\EditActivity;
use App\Filament\Resources\ActivityResource\Pages\ListActivities;
use App\Models\Activity;
use App\Models\Tour;
use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\ActivitySeeder;
use Database\Seeders\DemoTourSeeder;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Activities library: admin form (both validation layers), delete guard, seed list, use on tour days + API. */
final class ActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $user;
    }

    public function test_form_validates_on_the_server_and_saves(): void
    {
        $this->actingAsRole(Permissions::CONTENT_EDITOR);
        Activity::factory()->create(['name' => 'Jeep safari']);

        Livewire::test(CreateActivity::class)
            ->fillForm(['name' => '', 'slug' => 'x'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);

        Livewire::test(CreateActivity::class)
            ->fillForm(['name' => 'J', 'slug' => 'Bad Slug!', 'description' => str_repeat('x', 1001)])
            ->call('create')
            ->assertHasFormErrors(['name' => 'min', 'slug' => 'regex', 'description' => 'max']);

        Livewire::test(CreateActivity::class)
            ->fillForm(['name' => 'Jeep safari', 'slug' => 'jeep-safari-2'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);

        Livewire::test(CreateActivity::class)
            ->fillForm(['name' => 'White-water <b>rafting</b>', 'slug' => 'white-water-rafting', 'description' => '<script>x</script>Grade 2–3 rapids.'])
            ->call('create')
            ->assertHasNoFormErrors();

        $activity = Activity::where('slug', 'white-water-rafting')->firstOrFail();
        $this->assertSame('White-water rafting', $activity->name);
        $this->assertStringNotContainsString('<script>', (string) $activity->description);

        Livewire::test(EditActivity::class, ['record' => $activity->getRouteKey()])
            ->fillForm(['description' => 'Kelani River, Kitulgala.'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('Kelani River, Kitulgala.', $activity->fresh()->description);
    }

    public function test_activity_used_on_a_tour_cannot_be_force_deleted(): void
    {
        $this->actingAsRole(Permissions::SUPER_ADMIN);
        $activity = Activity::factory()->create();
        $tour = Tour::factory()->withDays([[]])->create();
        $tour->days->first()->activities()->attach($activity->id, ['sort_order' => 1]);
        $activity->delete();

        Livewire::test(EditActivity::class, ['record' => $activity->getRouteKey()])->callAction('forceDelete');

        $this->assertModelExists($activity);
    }

    public function test_admin_pages_render_in_the_tours_menu(): void
    {
        $this->actingAsRole(Permissions::SUPER_ADMIN);
        $activity = Activity::factory()->create();
        $admin = '/'.config('admin.path');

        $this->get("{$admin}/activities/create")->assertOk()->assertSee('maxlength="1000"', false);
        $this->get("{$admin}/activities/{$activity->slug}/edit")->assertOk();
        $this->get("{$admin}/activities")->assertOk()
            ->assertSeeInOrder(['Tours', 'Tour categories', 'Activities', 'Locations']);

        Livewire::test(ListActivities::class)->assertCanSeeTableRecords([$activity]);
    }

    public function test_users_without_permission_cannot_open_it(): void
    {
        $this->actingAsRole(Permissions::INQUIRY_MANAGER);

        $this->get('/'.config('admin.path').'/activities')->assertForbidden();
    }

    public function test_seeder_is_idempotent_and_fills_missing_descriptions_only(): void
    {
        Activity::create(['name' => 'jeep safari']);                           // e.g. moved from old free text
        Activity::create(['name' => 'Whale watching', 'description' => 'Mine.']); // admin's own text

        $this->seed(ActivitySeeder::class);
        $this->seed(ActivitySeeder::class);

        $this->assertSame(count(ActivitySeeder::ACTIVITIES), Activity::count());
        $this->assertNotEmpty(Activity::where('name', 'jeep safari')->value('description'));
        $this->assertSame('Mine.', Activity::where('name', 'Whale watching')->value('description'));
    }

    public function test_every_demo_tour_activity_exists_in_the_seed_library(): void
    {
        $used = collect(DemoTourSeeder::TOURS)->flatMap(fn (array $tour) => collect($tour[8])->flatMap(fn (array $day) => $day[7]))->unique();

        $this->assertSame([], $used->diff(array_keys(ActivitySeeder::ACTIVITIES))->values()->all());
    }

    public function test_public_tour_detail_lists_active_day_activities_in_order(): void
    {
        $rafting = Activity::factory()->create(['name' => 'Rafting']);
        $canyoning = Activity::factory()->create(['name' => 'Canyoning']);
        $hidden = Activity::factory()->create(['name' => 'Hidden', 'is_active' => false]);
        $tour = Tour::factory()->published()->withDays([[]])->create(['slug' => 'adventure']);
        $tour->days->first()->activities()->sync([
            $canyoning->id => ['sort_order' => 1], $rafting->id => ['sort_order' => 2], $hidden->id => ['sort_order' => 3],
        ]);

        $this->getJson('/api/v1/tours/adventure')->assertOk()
            ->assertJsonCount(2, 'data.itinerary.0.activities')
            ->assertJsonPath('data.itinerary.0.activities.0.name', 'Canyoning')
            ->assertJsonPath('data.itinerary.0.activities.1.name', 'Rafting')
            ->assertJsonStructure(['data' => ['itinerary' => [['activities' => [['id', 'slug', 'name', 'description', 'image', 'thumb']]]]]]);
    }
}
