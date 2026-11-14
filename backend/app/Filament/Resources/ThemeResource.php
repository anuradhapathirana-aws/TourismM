<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ThemeSide;
use App\Filament\Resources\ThemeResource\Pages;
use App\Filament\Support\AdminUi;
use App\Filament\Support\FormRules;
use App\Filament\Support\ImageFields;
use App\Models\Theme;
use App\Support\Validation\ThemeRules;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/** Theme cards around the home-page map. */
final class ThemeResource extends Resource
{
    protected static ?string $model = Theme::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Locations';

    protected static ?string $navigationLabel = 'Map themes';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        $rules = fn (?Model $record, Get $get): array => ThemeRules::rules($record instanceof Theme ? $record : null);

        return $form->columns(['lg' => 4])->schema([
            Section::make('Theme card')->columnSpan(['lg' => 3])->schema([
                AdminUi::grid([
                    FormRules::sluggedName(
                        TextInput::make('title')->required()->minLength(2)->maxLength(80)->rules(FormRules::for($rules, 'title'))
                    )->span('md'),
                    FormRules::slugInput($rules, 100)->span('md'),
                    ToggleButtons::make('side')
                        ->label('Column on the map')
                        ->options(ThemeSide::class)
                        ->default(ThemeSide::Left)
                        ->inline()
                        ->required()
                        ->tip('Which side of the map the card sits on (desktop). 3 cards per side works best.')
                        ->rules(FormRules::for($rules, 'side'))
                        ->span('md'),
                    TextInput::make('sort_order')->label('Sort')->integer()->default(0)->minValue(0)->maxValue(65535)
                        ->tip('Lower numbers come first.')
                        ->rules(FormRules::for($rules, 'sort_order'))->span('xs'),
                    Toggle::make('is_active')->label('Active')->default(true)
                        ->tip('Inactive themes are hidden from the website map.')
                        ->rules(FormRules::for($rules, 'is_active'))->span('xs'),
                ]),
            ]),
            Section::make('Card image')->columnSpan(['lg' => 1])->schema([ImageFields::single('image', 'Image')]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('media')->withCount('destinations'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('image')->collection('image')->conversion('thumb')->circular()->label(''),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('side')->badge(),
                Tables\Columns\TextColumn::make('destinations_count')->label('Points')->badge(),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([Tables\Filters\TrashedFilter::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListThemes::route('/'),
            'create' => Pages\CreateTheme::route('/create'),
            'edit' => Pages\EditTheme::route('/{record}/edit'),
        ];
    }
}
