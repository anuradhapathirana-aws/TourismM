<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\TourCategoryResource\Pages\CreateTourCategory;
use App\Filament\Resources\TourCategoryResource\Pages\EditTourCategory;
use App\Models\Tour;
use App\Models\TourCategory;
use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TourCategorySeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Tour categories: admin form (both validation layers), delete guard, public API. */
final class TourCategoryTest extends TestCase
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
        TourCategory::factory()->create(['name' => 'Beach Tours']);

        Livewire::test(CreateTourCategory::class)
            ->fillForm(['name' => 'B', 'slug' => 'Bad Slug!', 'icon' => 'Bad Icon', 'summary' => str_repeat('x', 301)])
            ->call('create')
            ->assertHasFormErrors(['name' => 'min', 'slug' => 'regex', 'icon' => 'regex', 'summary' => 'max']);

        Livewire::test(CreateTourCategory::class)
            ->fillForm(['name' => 'Beach Tours', 'slug' => 'beach-tours-2'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);

        Livewire::test(CreateTourCategory::class)
            ->fillForm(['name' => 'Wildlife <b>Tours</b>', 'slug' => 'wildlife-tours', 'icon' => 'binoculars', 'summary' => 'Leopards & elephants.'])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = TourCategory::where('slug', 'wildlife-tours')->firstOrFail();
        $this->assertSame('Wildlife Tours', $category->name);

        Livewire::test(EditTourCategory::class, ['record' => $category->getRouteKey()])
            ->assertFormFieldDoesNotExist('destinations')
            ->fillForm(['summary' => 'Leopards, elephants and whales.'])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_a_category_with_tours_cannot_be_force_deleted(): void
    {
        $this->actingAsRole(Permissions::SUPER_ADMIN);
        $category = TourCategory::factory()->create();
        Tour::factory()->create(['tour_category_id' => $category->id]);
        $category->delete(); // force delete is offered for deleted records only

        Livewire::test(EditTourCategory::class, ['record' => $category->getRouteKey()])
            ->callAction('forceDelete');

        $this->assertModelExists($category);
    }

    public function test_admin_pages_render_in_the_tours_menu(): void
    {
        $this->actingAsRole(Permissions::SUPER_ADMIN);
        $category = TourCategory::factory()->create();
        $admin = '/'.config('admin.path');

        $this->get("{$admin}/tour-categories/create")->assertOk()
            ->assertSee('data-lg-pattern', false)
            ->assertSee('maxlength="300"', false);
        $this->get("{$admin}/tour-categories/{$category->slug}/edit")->assertOk();

        $this->get("{$admin}/tour-categories")->assertOk()
            ->assertSeeInOrder(['Tours', 'Tour categories', 'Locations', 'Destination points']);
    }

    public function test_users_without_permission_cannot_open_it(): void
    {
        $this->actingAsRole(Permissions::INQUIRY_MANAGER);

        $this->get('/'.config('admin.path').'/tour-categories')->assertForbidden();
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(TourCategorySeeder::class);
        $this->seed(TourCategorySeeder::class);

        $this->assertSame(count(TourCategorySeeder::CATEGORIES), TourCategory::count());
    }

    public function test_public_api_lists_active_categories_with_published_tour_counts(): void
    {
        $beach = TourCategory::factory()->create(['name' => 'Beach Tours', 'sort_order' => 1]);
        TourCategory::factory()->create(['name' => 'Hidden Tours', 'is_active' => false]);
        Tour::factory()->published()->create(['tour_category_id' => $beach->id]);
        Tour::factory()->create(['tour_category_id' => $beach->id]); // draft: not counted

        $this->getJson('/api/v1/tour-categories')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'beach-tours')
            ->assertJsonPath('data.0.toursCount', 1)
            ->assertJsonStructure(['data' => [['id', 'slug', 'name', 'icon', 'summary', 'image', 'thumb', 'toursCount']]]);
    }
}
