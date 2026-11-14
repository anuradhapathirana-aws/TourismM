<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PlaceTypeResource\Pages;
use App\Filament\Support\AdminUi;
use App\Filament\Support\FormRules;
use App\Models\PlaceType;
use App\Support\Validation\PlaceTypeRules;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Kinds of destination point: Waterfall, Temple, Beach … */
final class PlaceTypeResource extends Resource
{
    protected static ?string $model = PlaceType::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Locations';

    protected static ?string $navigationLabel = 'Place types';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        $rules = fn (?Model $record, Get $get): array => PlaceTypeRules::rules($record instanceof PlaceType ? $record : null);

        return $form->schema([
            Section::make('Place type')->schema([
                AdminUi::grid([
                    FormRules::sluggedName(
                        TextInput::make('name')->required()->minLength(2)->maxLength(60)->rules(FormRules::for($rules, 'name'))
                    )->span('sm'),
                    FormRules::slugInput($rules, 80)->span('sm'),
                    TextInput::make('icon')
                        ->label('Icon')
                        ->prefix('bi-')
                        ->placeholder('droplet')
                        ->maxLength(60)
                        ->regex('/^[a-z0-9-]+$/')
                        ->livePattern('/^[a-z0-9-]+$/', 'Use the icon name only: lowercase letters, numbers and hyphens (e.g. droplet).')
                        ->tip('Optional Bootstrap icon name, e.g. droplet. Browse names at icons.getbootstrap.com.')
                        ->rules(FormRules::for($rules, 'icon'))
                        ->span('sm'),
                    TextInput::make('sort_order')->label('Sort')->integer()->default(0)->minValue(0)->maxValue(65535)
                        ->tip('Lower numbers come first.')
                        ->rules(FormRules::for($rules, 'sort_order'))->span('xs'),
                    Toggle::make('is_active')->label('Active')->default(true)
                        ->tip('Inactive types can’t be chosen for new points.')
                        ->rules(FormRules::for($rules, 'is_active'))->span('xs'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('destinations'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('icon')->formatStateUsing(fn (?string $state) => $state ? "bi-{$state}" : '—'),
                Tables\Columns\TextColumn::make('destinations_count')->label('Points')->badge(),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (PlaceType $record, Tables\Actions\DeleteAction $action): void {
                        if ($record->destinations()->withTrashed()->exists()) {
                            Notification::make()->warning()
                                ->title('This type is used by destination points. Deactivate it instead.')->send();
                            $action->cancel();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlaceTypes::route('/'),
            'create' => Pages\CreatePlaceType::route('/create'),
            'edit' => Pages\EditPlaceType::route('/{record}/edit'),
        ];
    }
}
