<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Enums\DestinationStatus;
use App\Enums\InquirySource;
use App\Enums\InquiryStatus;
use App\Enums\TourPace;
use App\Models\User;
use App\Support\Permissions;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Custom tour inquiry rules: one source for the website endpoint (POST /journey-requests), the admin form and tests.
 *
 * Rules that compare fields (departure after arrival, ≥ 1 adult, one age per child, rooms unless no hotel) are
 * closures fed with the other values in $data, so they work the same inside Filament (whose field names are
 * prefixed with "data.") and in a plain request.
 */
final class InquiryRules
{
    /** Website request: trip + the guest's contact details. @return array<string, list<mixed>> */
    public static function website(array $data): array
    {
        return [
            ...self::trip($data, website: true),
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
            'phone' => GuestRules::phone(),
            'country' => ['required', 'string', 'min:2', 'max:80'],
            'contact_method' => ['required', Rule::in(array_keys(config('inquiries.contact_methods')))],
            'referral' => ['nullable', Rule::in(config('inquiries.referrals'))],
            'website' => ['nullable', 'string', 'max:200'], // honeypot (checked by the controller)
        ];
    }

    /** Admin form: trip + pipeline fields. @return array<string, list<mixed>> */
    public static function admin(array $data): array
    {
        return [
            ...self::trip($data, website: false),
            'guest_id' => ['required', 'integer', Rule::exists('guests', 'id')->whereNull('deleted_at')],
            'status' => ['required', Rule::enum(InquiryStatus::class)],
            'source' => ['required', Rule::enum(InquirySource::class)],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'quoted_amount' => ['nullable', 'numeric', 'between:0,1000000'],
            'referral' => ['nullable', 'string', 'max:60'],
        ];
    }

    /**
     * Trip details shared by both entry points.
     * $website = true: dates must be in the future and points/activities must be public.
     *
     * @return array<string, list<mixed>>
     */
    public static function trip(array $data, bool $website): array
    {
        $c = config('inquiries');
        $guests = 'between:0,'.$c['max_guests_per_type'];
        $arrival = self::date($data['arrival_date'] ?? null);
        $children = (int) ($data['children'] ?? 0);
        $hotel = ($data['accommodation'] ?? null) !== 'none';

        return [
            'arrival_date' => array_values(array_filter([
                'required', 'date_format:Y-m-d',
                $website ? 'after_or_equal:today' : null,
                $website ? 'before_or_equal:'.now()->addYears($c['book_ahead_years'])->toDateString() : null,
            ])),
            'departure_date' => ['required', 'date_format:Y-m-d', self::departureAfter($arrival, $c['max_nights'])],
            'arrival_time' => ['nullable', 'date_format:H:i'],
            'flexible_dates' => ['boolean'],
            'arrival_airport' => ['nullable', Rule::in(array_keys($c['airports']))],
            'occasion' => ['nullable', Rule::in(array_keys($c['occasions']))],
            'pace' => ['required', Rule::enum(TourPace::class)],

            'men' => ['required', 'integer', $guests, self::atLeastOneAdult((int) ($data['women'] ?? 0))],
            'women' => ['required', 'integer', $guests],
            'children' => ['required', 'integer', $guests],
            'child_ages' => [$children > 0 ? 'required' : 'nullable', 'array', self::oneAgePerChild($children)],
            'child_ages.*' => ['required', 'integer', 'between:0,17'],

            'accommodation' => ['required', Rule::in(array_keys($c['accommodation']))],
            'rooms' => $hotel ? ['required', 'integer', 'between:1,'.$c['max_guests_per_type']] : ['nullable', 'integer', 'between:1,'.$c['max_guests_per_type']],
            'meal_plan' => [$hotel ? 'required' : 'nullable', Rule::in(array_keys($c['meal_plans']))],
            'vehicle' => ['required', Rule::in(array_keys($c['vehicles']))],
            'guide_language' => ['required', Rule::in($c['guide_languages'])],
            'budget' => ['nullable', Rule::in(array_keys($c['budgets']))],
            'dietary' => CommonRules::plainText(300),
            'accessibility' => CommonRules::plainText(300),

            'destination_ids' => ['nullable', 'array', 'max:'.$c['max_destinations']],
            'destination_ids.*' => ['integer', 'distinct', $website
                ? Rule::exists('destinations', 'id')->where('status', DestinationStatus::Published->value)->whereNull('deleted_at')
                : Rule::exists('destinations', 'id')->whereNull('deleted_at')],
            'activity_ids' => ['nullable', 'array', 'max:'.$c['max_activities']],
            'activity_ids.*' => ['integer', 'distinct', $website
                ? Rule::exists('activities', 'id')->where('is_active', true)->whereNull('deleted_at')
                : Rule::exists('activities', 'id')->whereNull('deleted_at')],
            'notes' => CommonRules::plainText($c['notes_max']),
        ];
    }

    /** "Change status" action and "Add note" on the timeline. @return array<string, list<mixed>> */
    public static function statusChange(): array
    {
        return [
            'status' => ['required', Rule::enum(InquiryStatus::class)],
            'quoted_amount' => ['nullable', 'numeric', 'between:0,1000000'],
            'note' => CommonRules::plainText(self::NOTE_MAX),
        ];
    }

    public const NOTE_MAX = 2000;

    /** Staff who can own an inquiry: active users with the inquiry-manager or super-admin role. */
    public static function assignableUsers(): array
    {
        return User::query()->where('is_active', true)
            ->whereHas('roles', fn (Builder $query) => $query->whereIn('name', [Permissions::SUPER_ADMIN, Permissions::INQUIRY_MANAGER]))
            ->orderBy('name')->pluck('name', 'id')->all();
    }

    private static function date(mixed $value): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return Carbon::createFromFormat('!Y-m-d', $value) ?: null;
    }

    private static function departureAfter(?Carbon $arrival, int $maxNights): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($arrival, $maxNights): void {
            $departure = self::date($value);
            if (! $arrival || ! $departure) {
                return; // date_format / required report the problem
            }
            if ($departure->lessThanOrEqualTo($arrival)) {
                $fail('The departure date must be at least one day after the arrival date.');
            } elseif ($arrival->diffInDays($departure) > $maxNights) {
                $fail("Trips can be at most {$maxNights} nights. Please contact us directly for longer stays.");
            }
        };
    }

    private static function atLeastOneAdult(int $women): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($women): void {
            if (is_numeric($value) && (int) $value + $women < 1) {
                $fail('Please add at least one adult (men or women).');
            }
        };
    }

    private static function oneAgePerChild(int $children): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($children): void {
            if (is_array($value) && count($value) !== $children) {
                $fail("Please give one age for each child ({$children}).");
            }
        };
    }
}
