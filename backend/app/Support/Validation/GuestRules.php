<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Models\Guest;
use Illuminate\Validation\Rule;

final class GuestRules
{
    /** + and digits with spaces, dashes, dots or brackets; 7–15 digits are checked separately. */
    public const PHONE_REGEX = '/^\+?[0-9][0-9\s().-]{5,28}$/';

    public const NOTES_MAX = 2000;

    /** Admin guest form. @return array<string, list<mixed>> */
    public static function rules(?Guest $guest = null): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190', Rule::unique('guests', 'email')->ignore($guest?->id)],
            'phone' => self::phone(required: false),
            'country' => ['nullable', 'string', 'min:2', 'max:80'],
            'preferred_contact' => ['nullable', Rule::in(array_keys(config('inquiries.contact_methods')))],
            'notes' => CommonRules::plainText(self::NOTES_MAX),
        ];
    }

    /** @return list<mixed> */
    public static function phone(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:30',
            'regex:'.self::PHONE_REGEX,
            function (string $attribute, mixed $value, \Closure $fail): void {
                $digits = strlen((string) preg_replace('/\D+/', '', (string) $value));
                if ($digits < 7 || $digits > 15) {
                    $fail('Enter the full phone number with country code, e.g. +44 7700 900123.');
                }
            },
        ];
    }
}
