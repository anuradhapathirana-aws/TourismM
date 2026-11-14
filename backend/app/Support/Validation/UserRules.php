<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Models\User;
use Closure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Admin staff accounts: used by the Users admin form and the make:admin command. */
final class UserRules
{
    /** Emails are stored lowercase, so compare case-insensitively on every database (MySQL and SQLite). */
    private static function uniqueEmail(?User $user): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($user): void {
            $taken = User::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim((string) $value))])
                ->when($user, fn ($query) => $query->whereKeyNot($user->getKey()))
                ->exists();

            if ($taken) {
                $fail('Another admin user already uses this email.');
            }
        };
    }

    /** Production also requires mixed case, numbers, symbols and a not-leaked password (AppServiceProvider). */
    public static function passwordMinLength(): int
    {
        return app()->isProduction() ? 12 : 8;
    }

    /** @return array<string, list<mixed>> */
    public static function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:150', self::uniqueEmail($user)],
            // Required when creating; optional on edit (blank = keep the current password).
            'password' => [$user ? 'nullable' : 'required', 'string', 'max:255', Password::defaults()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')],
            'is_active' => ['boolean'],
        ];
    }
}
