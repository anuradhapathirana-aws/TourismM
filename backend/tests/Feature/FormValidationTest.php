<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\DistrictResource\Pages\CreateDistrict;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\Province;
use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Two layers: instant browser checks (public/js/admin-validation.js, driven by field attributes)
 * and the full server rules (*Rules classes) on submit. The server layer is what these tests prove.
 */
final class FormValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['email' => 'boss@example.test']);
        $this->admin->assignRole(Permissions::SUPER_ADMIN);
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_pages_load_the_live_validation_script_and_field_constraints(): void
    {
        $html = $this->get('/'.config('admin.path').'/destinations/create')->assertOk()->getContent();

        $this->assertStringContainsString('js/admin-validation.js', $html);
        $this->assertStringContainsString('data-lg-pattern="^[a-z0-9]+(?:-[a-z0-9]+)*$"', $html); // slug
        $this->assertMatchesRegularExpression('/maxlength="300"/', $html);                          // summary
        $this->assertMatchesRegularExpression('/min="5.85"/', $html);                                // latitude bounds
    }

    public function test_user_form_validates_every_field_on_the_server(): void
    {
        $role = Role::findByName(Permissions::CONTENT_EDITOR)->id;

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'A', 'email' => 'not-an-email', 'password' => 'short', 'password_confirmation' => 'other', 'roles' => []])
            ->call('create')
            ->assertHasFormErrors(['name' => 'min', 'email' => 'email', 'password', 'roles' => 'required']);

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Kamal', 'email' => 'BOSS@example.test', 'password' => 'Long-enough-1', 'password_confirmation' => 'Long-enough-1', 'roles' => [$role]])
            ->call('create')
            ->assertHasFormErrors(['email']);

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Kamal', 'email' => 'kamal@example.test', 'password' => 'Long-enough-1', 'password_confirmation' => 'Different-1', 'roles' => [$role]])
            ->call('create')
            ->assertHasFormErrors(['password' => 'confirmed']);

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Kamal <b>P</b>', 'email' => 'Kamal@Example.test', 'password' => 'Long-enough-1', 'password_confirmation' => 'Long-enough-1', 'roles' => [$role]])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'kamal@example.test')->firstOrFail();
        $this->assertSame('Kamal P', $user->name);
        $this->assertTrue($user->hasRole(Permissions::CONTENT_EDITOR));
    }

    public function test_editing_a_user_keeps_the_password_when_left_blank(): void
    {
        $user = User::factory()->create(['email' => 'staff@example.test']);
        $user->assignRole(Permissions::CONTENT_EDITOR);
        $hash = $user->password;

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['name' => 'Staff Member', 'password' => '', 'password_confirmation' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($hash, $user->fresh()->password);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['email' => 'boss@example.test'])
            ->call('save')
            ->assertHasFormErrors(['email']);
    }

    public function test_district_form_uses_the_shared_rules(): void
    {
        $province = Province::query()->create(['name' => 'Uva', 'slug' => 'uva']);

        Livewire::test(CreateDistrict::class)
            ->fillForm(['name' => 'B', 'slug' => 'Bad Slug!', 'province_id' => null])
            ->call('create')
            ->assertHasFormErrors(['name' => 'min', 'province_id' => 'required']);

        Livewire::test(CreateDistrict::class)
            ->fillForm(['name' => 'Badulla', 'slug' => 'badulla', 'province_id' => $province->id])
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::test(CreateDistrict::class)
            ->fillForm(['name' => 'Badulla', 'slug' => 'badulla-2', 'province_id' => $province->id])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);
    }
}
