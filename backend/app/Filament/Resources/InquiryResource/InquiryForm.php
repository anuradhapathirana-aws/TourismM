<?php

declare(strict_types=1);

namespace App\Filament\Resources\InquiryResource;

use App\Enums\InquirySource;
use App\Enums\InquiryStatus;
use App\Enums\TourPace;
use App\Filament\Support\AdminUi;
use App\Filament\Support\FormRules;
use App\Models\Activity;
use App\Models\Destination;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Support\Validation\InquiryRules;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Staff form for an inquiry: create one taken by phone/WhatsApp/walk-in, or correct a website request.
 * Tabs keep it on one screen: Trip · Travellers & stay · Places & wishes. Side: pipeline + at a glance.
 * destination_ids / activity_ids are not columns: the pages fill and sync them (keeping the order picked).
 */
final class InquiryForm
{
    public static function schema(): array
    {
        $rules = fn (?Model $record, Get $get): array => InquiryRules::admin([
            'arrival_date' => $get('arrival_date'),
            'women' => $get('women'),
            'children' => $get('children'),
            'accommodation' => $get('accommodation'),
        ]);
        $c = config('inquiries');

        return [
            Group::make()->columnSpan(['lg' => 3])->schema([
                Tabs::make('Inquiry')->persistTabInQueryString()->tabs([
                    Tabs\Tab::make('Trip')->icon('heroicon-m-calendar-days')->schema([self::trip($rules, $c)]),
                    Tabs\Tab::make('Travellers & stay')->icon('heroicon-m-users')->schema([self::travellers($rules, $c)]),
                    Tabs\Tab::make('Places & wishes')->icon('heroicon-m-map-pin')
                        ->badge(fn (Get $get) => count($get('destination_ids') ?? []) + count($get('activity_ids') ?? []) ?: null)
                        ->schema([self::interests($rules, $c)]),
                ]),
            ]),
            Group::make()->columnSpan(['lg' => 1])->schema([
                Section::make('Pipeline')->schema([
                    Select::make('status')->options(InquiryStatus::class)->default(InquiryStatus::New)->required()->native(false)
                        ->tip('Every change is recorded on the timeline with your name.')
                        ->rules(FormRules::for($rules, 'status')),
                    Select::make('assigned_to')->label('Assigned to')
                        ->options(fn () => InquiryRules::assignableUsers())->searchable()
                        ->default(fn () => auth()->id())
                        ->tip('The staff member who plans this trip and replies to the guest.')
                        ->rules(FormRules::for($rules, 'assigned_to')),
                    TextInput::make('quoted_amount')->label('Quoted total')->numeric()->minValue(0)->maxValue(1000000)
                        ->prefix(config('tours.currency'))
                        ->tip('Total price sent to the guest (whole trip).')
                        ->rules(FormRules::for($rules, 'quoted_amount')),
                    Select::make('source')->options(InquirySource::class)->default(InquirySource::Phone)->required()->native(false)
                        ->tip('How the guest contacted us.')
                        ->rules(FormRules::for($rules, 'source')),
                ]),
                Section::make('At a glance')->schema([
                    Placeholder::make('glance_nights')->label('Length')
                        ->content(fn (Get $get) => self::nightsLabel($get('arrival_date'), $get('departure_date'))),
                    Placeholder::make('glance_guests')->label('Guests')
                        ->content(fn (Get $get) => (string) ((int) $get('men') + (int) $get('women') + (int) $get('children'))),
                    Placeholder::make('reference')->visibleOn(['edit'])
                        ->content(fn (?Inquiry $record) => $record?->reference),
                    Placeholder::make('received')->visibleOn(['edit'])
                        ->content(fn (?Inquiry $record) => $record?->created_at?->format('d M Y, H:i')),
                ]),
            ]),
        ];
    }

    private static function trip(Closure $rules, array $c): Grid
    {
        return AdminUi::grid([
            Select::make('guest_id')->label('Guest')
                ->relationship('guest', 'name')
                ->getOptionLabelFromRecordUsing(fn (Guest $record) => "{$record->name} · {$record->email}")
                ->searchable(['name', 'email', 'phone'])
                ->default(fn () => request()->integer('guest') ?: null)
                ->required()
                ->createOptionForm(GuestFields::schema())
                ->createOptionModalHeading('New guest')
                ->tip('Search by name, email or phone. "+" adds a new guest.')
                ->rules(FormRules::for($rules, 'guest_id'))
                ->span('lg'),
            Select::make('occasion')->options($c['occasions'])->native(false)
                ->rules(FormRules::for($rules, 'occasion'))->span('sm'),
            Select::make('pace')->options(TourPace::class)->default(TourPace::Moderate)->required()->native(false)
                ->rules(FormRules::for($rules, 'pace'))->span('sm'),
            DatePicker::make('arrival_date')->required()->live(onBlur: true)
                ->rules(FormRules::for($rules, 'arrival_date'))->span('sm'),
            DatePicker::make('departure_date')->required()->live(onBlur: true)
                ->minDate(fn (Get $get) => $get('arrival_date'))
                ->rules(FormRules::for($rules, 'departure_date'))->span('sm'),
            TimePicker::make('arrival_time')->seconds(false)
                ->tip('Flight landing time, if known.')
                ->rules(FormRules::for($rules, 'arrival_time'))->span('sm'),
            Toggle::make('flexible_dates')->label('Flexible dates')->inline(false)
                ->rules(FormRules::for($rules, 'flexible_dates'))->span('sm'),
            Select::make('arrival_airport')->label('Arriving at')->options($c['airports'])->native(false)
                ->rules(FormRules::for($rules, 'arrival_airport'))->span('lg'),
            Select::make('referral')->label('Heard about us via')
                ->options(array_combine($c['referrals'], $c['referrals']))->native(false)
                ->rules(FormRules::for($rules, 'referral'))->span('md'),
        ]);
    }

    private static function travellers(Closure $rules, array $c): Grid
    {
        $max = $c['max_guests_per_type'];
        $count = fn (string $name, string $label) => TextInput::make($name)->label($label)
            ->integer()->required()->default(0)->minValue(0)->maxValue($max)->live(onBlur: true)
            ->rules(FormRules::for($rules, $name))->span('xs');

        return AdminUi::grid([
            $count('men', 'Men (18+)')->validationMessages(['required' => 'Enter the number of men (0 if none).']),
            $count('women', 'Women (18+)'),
            $count('children', 'Children (<18)'),
            Repeater::make('child_ages')->label('Children’s ages')
                ->simple(
                    TextInput::make('age')->integer()->required()->minValue(0)->maxValue(17)
                        ->placeholder('Age (0 = under 1)')->rules(['required', 'integer', 'between:0,17']),
                )
                ->visible(fn (Get $get) => (int) $get('children') > 0)
                ->defaultItems(0)
                ->grid(3)
                ->addActionLabel('Add child age')
                ->reorderable(false)
                ->tip('One age per child (0 = under 1).')
                ->rules(FormRules::for($rules, 'child_ages'))
                ->span('xl'),

            Select::make('accommodation')->label('Hotel standard')->options($c['accommodation'])->default('comfort')->required()->native(false)->live()
                ->rules(FormRules::for($rules, 'accommodation'))->span('md'),
            TextInput::make('rooms')->integer()->minValue(1)->maxValue($max)->default(1)
                ->required(fn (Get $get) => $get('accommodation') !== 'none')
                ->hidden(fn (Get $get) => $get('accommodation') === 'none')
                ->rules(FormRules::for($rules, 'rooms'))->span('xs'),
            Select::make('meal_plan')->label('Meals')->options($c['meal_plans'])->default('bb')->native(false)
                ->required(fn (Get $get) => $get('accommodation') !== 'none')
                ->hidden(fn (Get $get) => $get('accommodation') === 'none')
                ->rules(FormRules::for($rules, 'meal_plan'))->span('sm'),
            Select::make('vehicle')->options($c['vehicles'])->default('recommend')->required()->native(false)
                ->rules(FormRules::for($rules, 'vehicle'))->span('sm'),
            Select::make('guide_language')->label('Guide language')
                ->options(array_combine($c['guide_languages'], $c['guide_languages']))->default('English')->required()->native(false)
                ->rules(FormRules::for($rules, 'guide_language'))->span('sm'),
            Select::make('budget')->label('Budget per person')
                ->options(collect($c['budgets'])->map(fn (string $label) => config('tours.currency').' '.$label)->all())->native(false)
                ->tip('Land tour only, per person, excluding international flights.')
                ->rules(FormRules::for($rules, 'budget'))->span('sm'),
            TextInput::make('dietary')->label('Dietary needs')->maxLength(300)
                ->rules(FormRules::for($rules, 'dietary'))->span('lg'),
            TextInput::make('accessibility')->label('Accessibility / medical needs')->maxLength(300)
                ->rules(FormRules::for($rules, 'accessibility'))->span('lg'),
        ]);
    }

    private static function interests(Closure $rules, array $c): Grid
    {
        return AdminUi::grid([
            Select::make('destination_ids')->label('Destinations')->multiple()->searchable()
                ->options(fn () => Destination::query()->with('city:id,name')->orderBy('name')->get(['id', 'name', 'city_id'])
                    ->groupBy(fn (Destination $point) => $point->city?->name ?? 'Other')
                    ->sortKeys()
                    ->map(fn ($points) => $points->pluck('name', 'id')->all())
                    ->all())
                ->maxItems($c['max_destinations'])
                ->tip('In the order the guest listed them.')
                ->rules(FormRules::for($rules, 'destination_ids'))
                ->span('full'),
            Select::make('activity_ids')->label('Activities')->multiple()->searchable()
                ->options(fn () => Activity::query()->ordered()->pluck('name', 'id')->all())
                ->maxItems($c['max_activities'])
                ->rules(FormRules::for($rules, 'activity_ids'))
                ->span('full'),
            Textarea::make('notes')->label('Guest’s wishes & special requests')->rows(6)->maxLength($c['notes_max'])
                ->tip('In the guest’s own words, including places or activities that are not in the lists.')
                ->rules(FormRules::for($rules, 'notes'))
                ->span('full'),
        ]);
    }

    public static function nightsLabel(mixed $from, mixed $to): string
    {
        try {
            $nights = $from && $to ? (int) Carbon::parse($from)->diffInDays(Carbon::parse($to), false) : 0;
        } catch (\Throwable) {
            $nights = 0;
        }

        return $nights > 0 ? "{$nights} ".($nights === 1 ? 'night' : 'nights').' · '.($nights + 1).' days' : 'Pick the dates';
    }
}
