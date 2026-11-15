<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Filament\Support\AdminUi;
use App\Filament\Support\FormRules;
use App\Filament\Support\ImageFields;
use App\Models\Activity;
use App\Support\Validation\ActivityRules;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/** Library of activities (Jeep safari, Tea factory tour …), picked per itinerary day on the tour form. */
final class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Tours';

    protected static ?string $navigationLabel = 'Activities';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        $rules = fn (?Model $record, Get $get): array => ActivityRules::rules($record instanceof Activity ? $record : null);

        return $form->columns(['lg' => 4])->schema([
            Section::make('Activity')->columnSpan(['lg' => 3])->schema([
                AdminUi::grid([
                    FormRules::sluggedName(
                        TextInput::make('name')->label('Activity name')->required()->minLength(2)->maxLength(100)
                            ->placeholder('White-water rafting')
                            ->rules(FormRules::for($rules, 'name'))
                    )->span('md'),
                    FormRules::slugInput($rules, 120)->span('md'),
                    TextInput::make('sort_order')->label('Sort')->integer()->default(0)->minValue(0)->maxValue(65535)
                        ->tip('Lower numbers come first.')
                        ->rules(FormRules::for($rules, 'sort_order'))->span('xs'),
                    Toggle::make('is_active')->label('Active')->default(true)
                        ->tip('Inactive activities can no longer be picked on tours and are hidden on the website.')
                        ->rules(FormRules::for($rules, 'is_active'))->span('xs'),
                    Textarea::make('description')
                        ->rows(4)
                        ->maxLength(ActivityRules::DESCRIPTION_MAX)
                        ->tip('What guests do, where and when (up to '.ActivityRules::DESCRIPTION_MAX.' characters).')
                        ->rules(FormRules::for($rules, 'description'))
                        ->span('full'),
                ]),
            ]),
            Section::make('Related images')->columnSpan(['lg' => 1])->schema([
                ImageFields::gallery('images')->label('Images')->hiddenLabel(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('media')->withCount('tourDays'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('images')->collection('images')->conversion('thumb')
                    ->circular()->limit(1)->label(''),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('description')->limit(70)
                    ->tooltip(fn (Activity $record) => $record->description)->toggleable(),
                Tables\Columns\TextColumn::make('tour_days_count')->label('Used on days')->badge()->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            'create' => Pages\CreateActivity::route('/create'),
            'edit' => Pages\EditActivity::route('/{record}/edit'),
        ];
    }
}
