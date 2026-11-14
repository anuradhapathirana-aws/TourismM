<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\City;
use App\Models\Destination;
use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Compact admin layout (AdminUi): every page renders, help text is a hover tip, row actions are icons. */
final class AdminUiTest extends TestCase
{
    use RefreshDatabase;

    private string $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(Permissions::SUPER_ADMIN);
        $this->actingAs($user);

        $this->admin = '/'.config('admin.path');
    }

    public function test_every_admin_page_renders_with_the_compact_layout(): void
    {
        $point = Destination::factory()->create();
        $city = $point->city;

        $pages = [
            '', '/cities', '/cities/create', "/cities/{$city->slug}/edit",
            '/destinations', '/destinations/create', "/destinations/{$point->slug}/edit",
            '/themes', '/themes/create', '/place-types', '/place-types/create',
            '/districts', '/districts/create', '/users', '/users/create',
        ];

        foreach ($pages as $page) {
            $this->get($this->admin.$page)
                ->assertOk()
                ->assertSee('css/admin.css', false);
        }
    }

    public function test_help_text_is_a_hover_tip_not_a_note_under_the_field(): void
    {
        $this->get("{$this->admin}/destinations/create")
            ->assertOk()
            ->assertSee('fi-fo-field-wrp-hint', false)
            ->assertSee('Lower numbers come first.')
            ->assertDontSee('fi-fo-field-wrp-helper-text', false);
    }

    public function test_table_row_actions_are_icon_buttons(): void
    {
        City::factory()->create();

        $this->get("{$this->admin}/cities")
            ->assertOk()
            ->assertSee('fi-ac-icon-btn-action', false)
            ->assertDontSee('fi-ac-link-action', false);
    }
}
