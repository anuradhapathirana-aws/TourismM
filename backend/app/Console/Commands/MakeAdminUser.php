<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Permissions;
use App\Support\Validation\UserRules;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/** Creates an admin user from the CLI (there is no public registration). */
final class MakeAdminUser extends Command
{
    protected $signature = 'make:admin
        {--name= : Full name}
        {--email= : Login email}
        {--password= : Password (omit to be prompted securely)}
        {--role=super-admin : super-admin | content-editor | inquiry-manager}';

    protected $description = 'Create an admin panel user with a role';

    public function handle(): int
    {
        $data = [
            'name' => $this->option('name') ?: text('Full name', required: true),
            'email' => $this->option('email') ?: text('Email', required: true),
            'password' => $this->option('password') ?: password('Password', required: true),
            'role' => $this->option('role') ?: select('Role', array_keys(Permissions::roles())),
        ];

        $userRules = UserRules::rules();

        $validator = Validator::make($data, [
            'name' => $userRules['name'],
            'email' => $userRules['email'],
            'password' => $userRules['password'],
            'role' => ['required', Rule::in(array_keys(Permissions::roles()))],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        if (! Role::where('name', $data['role'])->exists()) {
            $this->components->error('Roles are missing. Run: php artisan db:seed --class=RolePermissionSeeder');

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'],
            'is_active' => true,
        ]);
        $user->assignRole($data['role']);

        $this->components->info("Admin created: {$user->email} ({$data['role']})");
        $this->components->twoColumnDetail('Login URL', url(config('admin.path').'/login'));

        return self::SUCCESS;
    }
}
