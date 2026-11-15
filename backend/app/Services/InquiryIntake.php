<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InquirySource;
use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Models\Guest;
use App\Models\Inquiry;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Saves a validated website request (InquiryRules::website) in one transaction:
 *  1. Guest: found by email (case-insensitive, deleted guests are restored) or created.
 *     A returning guest's name, phone, country and preferred contact are updated to the latest values.
 *  2. Inquiry: linked to the guest, status New, source Website.
 *  3. Picked destination points and activities, in the guest's order.
 */
final class InquiryIntake
{
    /** Columns copied from the request into the inquiry as they are. */
    private const TRIP_FIELDS = [
        'arrival_date', 'arrival_time', 'departure_date', 'flexible_dates', 'arrival_airport', 'occasion', 'pace',
        'men', 'women', 'children', 'child_ages',
        'accommodation', 'rooms', 'meal_plan', 'vehicle', 'guide_language', 'budget', 'dietary', 'accessibility',
        'notes', 'referral',
    ];

    /** @param array<string, mixed> $data validated input */
    public function fromWebsite(array $data, ?string $ip = null, ?string $userAgent = null): Inquiry
    {
        return DB::transaction(function () use ($data, $ip, $userAgent): Inquiry {
            $guest = $this->guest($data);

            $trip = Arr::only($data, self::TRIP_FIELDS);
            $trip['child_ages'] = (int) ($data['children'] ?? 0) > 0 ? array_map('intval', $data['child_ages'] ?? []) : null;
            if (($data['accommodation'] ?? null) === 'none') {
                $trip['rooms'] = null;
                $trip['meal_plan'] = null;
            }

            $inquiry = $guest->inquiries()->create([
                ...$trip,
                'type' => InquiryType::CustomTour,
                'source' => InquirySource::Website,
                'status' => InquiryStatus::New,
                'ip_address' => $ip,
                'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 255) : null,
            ]);

            $inquiry->destinations()->sync(self::ordered($data['destination_ids'] ?? []));
            $inquiry->activities()->sync(self::ordered($data['activity_ids'] ?? []));

            return $inquiry;
        });
    }

    /** @param array<string, mixed> $data */
    private function guest(array $data): Guest
    {
        $email = mb_strtolower(trim((string) $data['email']));
        $guest = Guest::withTrashed()->where('email', $email)->lockForUpdate()->first() ?? new Guest(['email' => $email]);

        if ($guest->trashed()) {
            $guest->restore();
        }

        $guest->fill([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'country' => $data['country'],
            'preferred_contact' => $data['contact_method'],
        ])->save();

        return $guest;
    }

    /** [12, 5] → [12 => ['sort_order' => 1], 5 => ['sort_order' => 2]] @return array<int, array{sort_order: int}> */
    public static function ordered(array $ids): array
    {
        $out = [];
        foreach (array_values(array_unique(array_map('intval', $ids))) as $i => $id) {
            $out[$id] = ['sort_order' => $i + 1];
        }

        return $out;
    }
}
