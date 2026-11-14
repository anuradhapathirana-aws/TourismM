<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Closure;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;

/**
 * Latitude / longitude inputs with:
 *  - a "paste from Google Maps" helper ("6.8768, 81.0608" → both fields)
 *  - a link to check the point on Google Maps
 * Bounds come from config/locations.php (same box the website map draws).
 * Returns fields for a parent AdminUi::grid(): paste (md) + lat (sm) + lng (sm).
 */
final class GeoFields
{
    /** "6.8768, 81.0608" or "6.8768 81.0608" as copied from Google Maps. */
    private const PASTE_REGEX = '/^\s*(-?\d{1,2}(?:\.\d+)?)\s*[,\s]\s*(-?\d{1,3}(?:\.\d+)?)\s*$/';

    /** @return list<TextInput> */
    public static function make(Closure $ruleSet): array
    {
        $b = config('locations.bounds');

        return [
            TextInput::make('paste_coordinates')
                ->label('Paste from Google Maps')
                ->placeholder('e.g. 6.8768, 81.0608')
                ->tip('Right-click the place in Google Maps → click the coordinates to copy them → paste here. Latitude and longitude fill in automatically.')
                ->dehydrated(false)
                ->live(onBlur: true)
                ->livePattern(self::PASTE_REGEX, 'Paste coordinates like 6.8768, 81.0608 (latitude, longitude).')
                ->afterStateUpdated(function (?string $state, Set $set): void {
                    if (preg_match(self::PASTE_REGEX, (string) $state, $m)) {
                        $set('latitude', round((float) $m[1], 7));
                        $set('longitude', round((float) $m[2], 7));
                        $set('paste_coordinates', null);
                    }
                })
                ->span('md'),
            TextInput::make('latitude')
                ->required()
                ->numeric()
                ->minValue($b['lat_min'])
                ->maxValue($b['lat_max'])
                ->step('any')
                ->live(onBlur: true)
                ->tip("Between {$b['lat_min']} and {$b['lat_max']} (Sri Lanka). The pin on the website map is placed from these coordinates.")
                ->rules(FormRules::for($ruleSet, 'latitude'))
                ->span('sm'),
            TextInput::make('longitude')
                ->required()
                ->numeric()
                ->minValue($b['lng_min'])
                ->maxValue($b['lng_max'])
                ->step('any')
                ->live(onBlur: true)
                ->tip("Between {$b['lng_min']} and {$b['lng_max']} (Sri Lanka).")
                ->rules(FormRules::for($ruleSet, 'longitude'))
                ->suffixAction(
                    Action::make('checkOnMap')
                        ->icon('heroicon-m-map-pin')
                        ->tooltip('Check on Google Maps')
                        ->url(fn (Get $get): ?string => is_numeric($get('latitude')) && is_numeric($get('longitude'))
                            ? 'https://www.google.com/maps?q='.(float) $get('latitude').','.(float) $get('longitude')
                            : null, shouldOpenInNewTab: true)
                )
                ->span('sm'),
        ];
    }
}
