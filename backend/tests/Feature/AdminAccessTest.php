<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The admin is a private entrance at /{ADMIN_PATH} (phpunit.xml: secret-admin-test). */
final class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private const PATH = '/secret-admin-test';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function staff(?string $role, bool $active = true): User
    {
        $user = User::factory()->create(['is_active' => $active]);
        if ($role) {
            $user->assignRole($role);
        }

        return $user;
    }

    public function test_admin_is_not_reachable_on_guessable_paths(): void
    {
        foreach (['/admin', '/admin/login', '/login', '/dashboard', '/filament'] as $path) {
            $this->get($path)->assertNotFound();
        }

        // The site root reveals nothing about the admin
        $this->get('/')->assertOk()->assertDontSee('secret-admin-test');
    }

    public function test_private_login_page_is_served_and_not_indexed(): void
    {
        $this->get(self::PATH.'/login')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_guests_are_sent_to_the_private_login(): void
    {
        $this->get(self::PATH.'/cities')->assertRedirect(self::PATH.'/login');
    }

    public function test_users_without_a_role_or_inactive_users_are_refused(): void
    {
        $this->actingAs($this->staff(null))->get(self::PATH)->assertForbidden();
        $this->actingAs($this->staff(Permissions::SUPER_ADMIN, active: false))->get(self::PATH)->assertForbidden();
    }

    public function test_super_admin_reaches_every_module(): void
    {
        $admin = $this->staff(Permissions::SUPER_ADMIN);

        foreach (['', '/cities', '/destinations', '/themes', '/place-types', '/districts', '/users', '/cities/create', '/destinations/create'] as $page) {
            $this->actingAs($admin)->get(self::PATH.$page)->assertOk();
        }
    }

    public function test_content_editor_manages_locations_but_not_users_or_reference_data(): void
    {
        $editor = $this->staff(Permissions::CONTENT_EDITOR);

        $this->actingAs($editor)->get(self::PATH.'/destinations')->assertOk();
        $this->actingAs($editor)->get(self::PATH.'/cities/create')->assertOk();
        $this->actingAs($editor)->get(self::PATH.'/users')->assertForbidden();
        $this->actingAs($editor)->get(self::PATH.'/districts')->assertForbidden();
    }

    public function test_make_admin_command_validates_and_creates_a_super_admin(): void
    {
        $this->artisan('make:admin', ['--name' => 'Owner', '--email' => 'not-an-email', '--password' => 'secret123'])
            ->assertFailed();

        $this->artisan('make:admin', ['--name' => 'Owner', '--email' => 'Owner@Example.com', '--password' => 'secret123'])
            ->assertSuccessful();

        $user = User::where('email', 'owner@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole(Permissions::SUPER_ADMIN));
    }
}
