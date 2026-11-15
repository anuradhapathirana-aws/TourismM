<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Validation\InquiryRules;
use Illuminate\Foundation\Http\FormRequest;

/** POST /journey-requests ("Build your own journey" popup). Public: no auth, rate-limited per IP. */
final class StoreJourneyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return InquiryRules::website($this->all());
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'child_ages.*' => 'child age',
            'destination_ids.*' => 'destination',
            'activity_ids.*' => 'activity',
            'contact_method' => 'contact method',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'arrival_date.after_or_equal' => 'The arrival date is in the past.',
            'arrival_date.before_or_equal' => 'We take requests up to '.config('inquiries.book_ahead_years').' years ahead.',
            'destination_ids.*.exists' => 'One of the chosen places is no longer available. Please remove it.',
            'activity_ids.*.exists' => 'One of the chosen activities is no longer available. Please remove it.',
            'email.email' => 'This email looks incomplete. Check it has an @ and a domain.',
            'phone.regex' => 'Enter the full phone number with country code, e.g. +44 7700 900123.',
        ];
    }
}
