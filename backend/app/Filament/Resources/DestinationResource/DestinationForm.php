<?php

declare(strict_types=1);

namespace App\Filament\Resources\DestinationResource;

use App\Enums\DestinationStatus;
use App\Filament\Support\AdminUi;
use App\Filament\Support\FormRules;
use App\Filament\Support\GeoFields;
use App\Filament\Support\ImageFields;
use App\Models\Destination;
use App\Support\Validation\DestinationRules;
use Closure;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Destination point form, shared by the Destinations resource and the City → Points relation manager.
 *
 * @param  Closure(Get): ?int  $cityId  resolves the city the point belongs to (form field or owner city)
 */
final class DestinationForm
{
    public static function schema(Closure $cityId, bool $withCitySelect = true): array
    {
        $rules = fn (?Model $record, Get $get): array => DestinationRules::rules(
            $record instanceof Destination ? $record : null,
            $cityId($get),
        );

        $requiresCover = function (Get $get): bool {
            $status = $get('status');
            $status = $status instanceof DestinationStatus ? $status : DestinationStatus::tryFrom((string) $status);

            return $status === DestinationStatus::Published || (bool) $get('is_featured');
        };

        return [
            Group::make()->columnSpan(['lg' => 3])->schema([
                Section::make('Destination point')->schema([
                    AdminUi::grid([
                        Select::make('city_id')
                            ->label('City')
                            ->relationship('city', 'name', fn (Builder $query) => $query->orderBy('name'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->rules(FormRules::for($rules, 'city_id'))
                            ->visible($withCitySelect)
                            ->span('sm'),
                        Select::make('place_type_id')
                            ->label('Type')
                            ->relationship('placeType', 'name', fn (Builder $query) => $query->where('is_active', true)->orderBy('sort_order'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->rules(FormRules::for($rules, 'place_type_id'))
                            ->span('sm'),
                        FormRules::sluggedName(
                            TextInput::make('name')
                                ->required()
                                ->minLength(2)
                                ->maxLength(150)
                                ->rules(FormRules::for($rules, 'name'))
                                ->validationMessages(['unique' => 'This city already has a point with this name.'])
                        )->span('sm'),
                        FormRules::slugInput($rules, 170)->span('sm'),
                        Textarea::make('summary')
                            ->required()
                            ->minLength(10)
                            ->maxLength(300)
                            ->rows(2)
                            ->tip('One or two sentences (10–300 characters). Shown on cards and the website map.')
                            ->rules(FormRules::for($rules, 'summary'))
                            ->span('full'),
                        RichEditor::make('description')
                            ->toolbarButtons(['bold', 'italic', 'underline', 'h2', 'h3', 'bulletList', 'orderedList', 'blockquote', 'link', 'undo', 'redo'])
                            ->maxLength(20000)
                            ->tip('Full article on the place page.')
                            ->rules(FormRules::for($rules, 'description'))
                            ->span('full'),
                    ]),
                ]),

                Section::make('Map location')->schema([AdminUi::grid(GeoFields::make($rules))]),

                Section::make('Photos')->schema([
                    AdminUi::grid([
                        ImageFields::single('cover')
                            ->required($requiresCover)
                            ->validationMessages(['required' => 'A cover image is required to publish or feature a point.'])
                            ->span('md'),
                        ImageFields::gallery()->span('xl'),
                    ]),
                ]),

                Section::make('Visitor information')->collapsible()->schema([
                    AdminUi::grid([
                        TextInput::make('address')->maxLength(255)->rules(FormRules::for($rules, 'address'))->span('lg'),
                        TextInput::make('opening_hours')->placeholder('Daily 07:00–17:30')->maxLength(150)->rules(FormRules::for($rules, 'opening_hours'))->span('sm'),
                        TextInput::make('entry_fee')->placeholder('USD 30 (foreigners)')->maxLength(150)->rules(FormRules::for($rules, 'entry_fee'))->span('sm'),
                        TextInput::make('best_time_to_visit')->placeholder('December – April')->maxLength(150)->rules(FormRules::for($rules, 'best_time_to_visit'))->span('sm'),
                        TextInput::make('visit_duration_minutes')
                            ->label('Visit (minutes)')
                            ->integer()
                            ->minValue(5)
                            ->maxValue(1440)
                            ->tip('Typical time a visitor spends here, 5–1440 minutes.')
                            ->rules(FormRules::for($rules, 'visit_duration_minutes'))
                            ->span('xs'),
                    ]),
                ]),

                Section::make('SEO')->collapsed()->schema([
                    AdminUi::grid([
                        TextInput::make('meta_title')->maxLength(70)->tip('Search-result title, up to 70 characters. Uses the name when empty.')
                            ->rules(FormRules::for($rules, 'meta_title'))->span('md'),
                        Textarea::make('meta_description')->maxLength(160)->rows(2)->tip('Search-result snippet, up to 160 characters. Uses the summary when empty.')
                            ->rules(FormRules::for($rules, 'meta_description'))->span('xl'),
                    ]),
                ]),
            ]),

            Group::make()->columnSpan(['lg' => 1])->schema([
                Section::make('Publishing')->schema([
                    ToggleButtons::make('status')
                        ->options(DestinationStatus::class)
                        ->default(DestinationStatus::Draft)
                        ->inline()
                        ->required()
                        ->live()
                        ->tip('Only published points appear on the website. Publishing needs a cover image.')
                        ->rules(FormRules::for($rules, 'status')),
                    Toggle::make('is_featured')
                        ->label('Featured')
                        ->tip('Also shown in the home-page photo mosaic. Needs a cover image.')
                        ->live()
                        ->rules(FormRules::for($rules, 'is_featured')),
                    TextInput::make('sort_order')->label('Sort order')->integer()->default(0)->minValue(0)->maxValue(65535)
                        ->tip('Lower numbers come first.')
                        ->rules(FormRules::for($rules, 'sort_order')),
                ]),
                Section::make('Map themes')
                    ->schema([
                        CheckboxList::make('themes')
                            ->label('Show under')
                            ->tip('The theme cards around the website map that reveal this point when hovered.')
                            ->relationship('themes', 'title', fn (Builder $query) => $query->orderBy('sort_order'))
                            ->maxItems(6)
                            ->rules(FormRules::for($rules, 'themes'))
                            ->nestedRecursiveRules(DestinationRules::rules()['themes.*']),
                    ]),
            ]),
        ];
    }
}
