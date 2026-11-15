<?php

declare(strict_types=1);

namespace App\Filament\Resources\InquiryResource;

use App\Filament\Support\AdminUi;
use App\Filament\Support\FormRules;
use App\Models\Guest;
use App\Support\Validation\GuestRules;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Model;

/** Guest fields: the Guests form and the "+ new guest" modal on the inquiry form use the same schema and rules. */
final class GuestFields
{
    /** @return list<Component> */
    public static function schema(): array
    {
        $rules = fn (?Model $record, Get $get): array => GuestRules::rules($record instanceof Guest ? $record : null);

        return [
            AdminUi::grid([
                TextInput::make('name')->required()->minLength(2)->maxLength(120)->autocomplete(false)
                    ->rules(FormRules::for($rules, 'name'))->span('md'),
                TextInput::make('email')->email()->required()->maxLength(190)
                    ->tip('Used to recognise the guest when they send another request.')
                    ->validationMessages(['unique' => 'A guest with this email already exists. Search for them instead.'])
                    ->rules(FormRules::for($rules, 'email'))->span('md'),
                TextInput::make('phone')->label('Phone / WhatsApp')->tel()->maxLength(30)
                    ->regex(GuestRules::PHONE_REGEX)
                    ->livePattern(GuestRules::PHONE_REGEX, 'Use digits with an optional + and spaces, e.g. +44 7700 900123.')
                    ->rules(FormRules::for($rules, 'phone'))->span('sm'),
                TextInput::make('country')->minLength(2)->maxLength(80)
                    ->rules(FormRules::for($rules, 'country'))->span('sm'),
                Select::make('preferred_contact')->label('Preferred contact')->options(config('inquiries.contact_methods'))->native(false)
                    ->rules(FormRules::for($rules, 'preferred_contact'))->span('sm'),
                Textarea::make('notes')->label('Staff notes')->rows(3)->maxLength(GuestRules::NOTES_MAX)
                    ->tip('Private notes about the guest (never shown to them).')
                    ->rules(FormRules::for($rules, 'notes'))->span('full'),
            ]),
        ];
    }
}
