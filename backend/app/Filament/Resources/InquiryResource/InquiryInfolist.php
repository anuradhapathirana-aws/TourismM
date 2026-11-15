<?php

declare(strict_types=1);

namespace App\Filament\Resources\InquiryResource;

use App\Filament\Resources\GuestResource;
use App\Models\Inquiry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;

/**
 * Read-only inquiry page: everything about the guest and the trip on one screen.
 * Main column: guest, trip, travellers & stay, places & activities, wishes. Side: pipeline.
 * The timeline (notes + status history) renders below via TimelineRelationManager.
 */
final class InquiryInfolist
{
    public static function make(Infolist $infolist): Infolist
    {
        $dash = '—';

        return $infolist->columns(['lg' => 4])->schema([
            Group::make()->columnSpan(['lg' => 3])->schema([
                Section::make('Guest')->icon('heroicon-m-user')->schema([
                    Grid::make(['default' => 1, 'md' => 3, 'xl' => 6])->schema([
                        TextEntry::make('guest.name')->label('Name')->weight('bold')->columnSpan(['xl' => 2])
                            ->url(fn (Inquiry $record) => $record->guest && ! $record->guest->trashed() && auth()->user()?->can('update', $record->guest)
                                ? GuestResource::getUrl('edit', ['record' => $record->guest]) : null),
                        TextEntry::make('guest.email')->label('Email')->copyable()->columnSpan(['xl' => 2])
                            ->url(fn (Inquiry $record) => 'mailto:'.$record->guest?->email),
                        TextEntry::make('guest.phone')->label('Phone / WhatsApp')->copyable()->placeholder($dash)
                            ->url(fn (Inquiry $record) => InquiryActions::whatsappUrl($record), shouldOpenInNewTab: true),
                        TextEntry::make('guest.country')->label('Country')->placeholder($dash),
                        TextEntry::make('preferred_contact')->label('Prefers')->placeholder($dash)
                            ->state(fn (Inquiry $record) => $record->guest?->preferredContactLabel()),
                        TextEntry::make('other_inquiries')->label('Other requests')
                            ->state(fn (Inquiry $record) => max(0, (int) $record->guest?->inquiries_count - 1))
                            ->badge()->color(fn (int $state) => $state > 0 ? 'warning' : 'gray')
                            ->tooltip('Earlier or later inquiries from the same guest (see the guest page).'),
                        TextEntry::make('referral')->label('Heard via')->placeholder($dash),
                    ]),
                ]),

                Section::make('Trip')->icon('heroicon-m-calendar-days')->schema([
                    Grid::make(['default' => 1, 'md' => 3, 'xl' => 6])->schema([
                        TextEntry::make('dates')->label('Dates')->columnSpan(['xl' => 2])
                            ->state(fn (Inquiry $record) => $record->arrival_date?->format('D d M Y').' → '.$record->departure_date?->format('D d M Y')),
                        TextEntry::make('length')->label('Length')
                            ->state(fn (Inquiry $record) => InquiryForm::nightsLabel($record->arrival_date, $record->departure_date)),
                        IconEntry::make('flexible_dates')->label('Flexible')->boolean(),
                        TextEntry::make('arrival')->label('Arrival')->columnSpan(['xl' => 2])->placeholder($dash)
                            ->state(fn (Inquiry $record) => trim((Inquiry::optionLabel('airports', $record->arrival_airport) ?? '')
                                .($record->arrival_time ? ' at '.substr((string) $record->arrival_time, 0, 5) : '')) ?: null),
                        TextEntry::make('occasion')->placeholder($dash)
                            ->formatStateUsing(fn (?string $state) => Inquiry::optionLabel('occasions', $state)),
                        TextEntry::make('pace')->placeholder($dash),
                    ]),
                ]),

                Section::make('Travellers & stay')->icon('heroicon-m-users')->schema([
                    Grid::make(['default' => 1, 'md' => 3, 'xl' => 6])->schema([
                        TextEntry::make('travellers')->label('Travellers')->columnSpan(['md' => 2, 'xl' => 3])
                            ->state(fn (Inquiry $record) => $record->travellersLabel()),
                        TextEntry::make('total')->label('Total guests')->badge()
                            ->state(fn (Inquiry $record) => $record->guestCount()),
                        TextEntry::make('accommodation')->label('Hotels')->columnSpan(['xl' => 2])
                            ->formatStateUsing(fn (?string $state) => Inquiry::optionLabel('accommodation', $state)),
                        TextEntry::make('rooms')->placeholder($dash),
                        TextEntry::make('meal_plan')->label('Meals')->placeholder($dash)
                            ->formatStateUsing(fn (?string $state) => Inquiry::optionLabel('meal_plans', $state)),
                        TextEntry::make('vehicle')->placeholder($dash)
                            ->formatStateUsing(fn (?string $state) => Inquiry::optionLabel('vehicles', $state)),
                        TextEntry::make('guide_language')->label('Guide language')->placeholder($dash),
                        TextEntry::make('budget')->label('Budget')->placeholder('Not sure yet')->columnSpan(['xl' => 2])
                            ->state(fn (Inquiry $record) => $record->budgetLabel()),
                        TextEntry::make('dietary')->label('Dietary needs')->placeholder($dash)->columnSpan(['md' => 3, 'xl' => 3]),
                        TextEntry::make('accessibility')->label('Accessibility / medical')->placeholder($dash)->columnSpan(['md' => 3, 'xl' => 3]),
                    ]),
                ]),

                Section::make('Places, activities & wishes')->icon('heroicon-m-map-pin')->schema([
                    TextEntry::make('destinations.name')->label('Destinations')->badge()->color('primary')->placeholder('None picked'),
                    TextEntry::make('activities.name')->label('Activities')->badge()->color('gray')->placeholder('None picked'),
                    TextEntry::make('notes')->label('Wishes & special requests (guest’s own words)')
                        ->placeholder('No extra wishes.')
                        // keep the guest's line breaks; the text is escaped first, so this is safe HTML
                        ->formatStateUsing(fn (?string $state) => $state === null ? null : nl2br(e($state)))->html(),
                ]),
            ]),

            Group::make()->columnSpan(['lg' => 1])->schema([
                Section::make('Pipeline')->icon('heroicon-m-funnel')->schema([
                    TextEntry::make('reference')->copyable()->weight('bold'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('assignee.name')->label('Assigned to')->placeholder('Nobody yet'),
                    TextEntry::make('quoted_amount')->label('Quoted total')->placeholder('Not quoted')
                        ->formatStateUsing(fn ($state) => $state !== null ? config('tours.currency').' '.number_format((float) $state, 2) : null),
                    TextEntry::make('source')->badge()->color('gray'),
                    TextEntry::make('type')->badge()->color('gray'),
                    TextEntry::make('created_at')->label('Received')->dateTime('d M Y, H:i')
                        ->tooltip(fn (Inquiry $record) => $record->created_at?->diffForHumans()),
                    TextEntry::make('closed_at')->label('Closed')->dateTime('d M Y, H:i')->visible(fn (Inquiry $record) => $record->closed_at !== null),
                ]),
            ]),
        ]);
    }
}
