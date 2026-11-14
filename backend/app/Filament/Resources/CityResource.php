<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CityResource\Pages;
use App\Filament\Resources\CityResource\RelationManagers\DestinationsRelationManager;
use App\Filament\Support\AdminUi;
use App\Filament\Support\FormRules;
use App\Filament\Support\GeoFields;
use App\Filament\Support\ImageFields;
use App\Models\City;
use App\Models\District;
use App\Support\Validation\CityRules;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class CityResource extends Resource
{
    protected static ?string $model = City::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Locations';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        $rules = fn (?Model $record, Get $get): array => CityRules::rules(
            $record instanceof City ? $record : null,
            filled($get('district_id')) ? (int) $get('district_id') : null,
        );

        return $form->columns(['lg' => 4])->schema([
            Group::make()->columnSpan(['lg' => 3])->schema([
                Section::make('City')->schema([
                    AdminUi::grid([
                        FormRules::sluggedName(
                            TextInput::make('name')->required()->minLength(2)->maxLength(120)
                                ->rules(FormRules::for($rules, 'name'))
                                ->validationMessages(['unique' => 'This district already has a city with this name.'])
                        )->span('md'),
                        FormRules::slugInput($rules, 140)->span('md'),
                        Select::make('district_id')
                            ->label('District')
                            ->relationship('district', 'name', fn (Builder $query) => $query->with('province:id,name')->orderBy('name'))
                            ->getOptionLabelFromRecordUsing(fn (District $record) => "{$record->name} ({$record->province?->name})")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->rules(FormRules::for($rules, 'district_id'))
                            ->span('md'),
                        Textarea::make('summary')->maxLength(300)->rows(2)
                            ->tip('One or two sentences, up to 300 characters.')
                            ->rules(FormRules::for($rules, 'summary'))->span('full'),
                        RichEditor::make('description')
                            ->toolbarButtons(['bold', 'italic', 'underline', 'h2', 'h3', 'bulletList', 'orderedList', 'blockquote', 'link', 'undo', 'redo'])
                            ->maxLength(20000)
                            ->rules(FormRules::for($rules, 'description'))
                            ->span('full'),
                    ]),
                ]),
                Section::make('City centre on the map')->schema([AdminUi::grid(GeoFields::make($rules))]),
                Section::make('SEO')->collapsed()->schema([
                    AdminUi::grid([
                        TextInput::make('meta_title')->maxLength(70)->tip('Search-result title, up to 70 characters.')
                            ->rules(FormRules::for($rules, 'meta_title'))->span('md'),
                        Textarea::make('meta_description')->maxLength(160)->rows(2)->tip('Search-result snippet, up to 160 characters.')
                            ->rules(FormRules::for($rules, 'meta_description'))->span('xl'),
                    ]),
                ]),
            ]),
            Group::make()->columnSpan(['lg' => 1])->schema([
                Section::make('Publishing')->schema([
                    Toggle::make('is_active')->label('Active')->default(true)
                        ->tip('Inactive cities hide all of their points from the website.')
                        ->rules(FormRules::for($rules, 'is_active')),
                    TextInput::make('sort_order')->label('Sort order')->integer()->default(0)->minValue(0)->maxValue(65535)
                        ->tip('Lower numbers come first.')
                        ->rules(FormRules::for($rules, 'sort_order')),
                ]),
                Section::make('Cover image')->schema([ImageFields::single('cover')->label('Image')]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['district:id,name', 'media'])->withCount('destinations'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('cover')->collection('cover')->conversion('thumb')->square()->label(''),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('summary')->limit(60)->tooltip(fn (City $record) => $record->summary)
                    ->toggleable()->color('gray'),
                Tables\Columns\TextColumn::make('district.name')->label('District')->sortable(),
                Tables\Columns\TextColumn::make('destinations_count')->label('Points')->badge()->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('district')->relationship('district', 'name')->searchable()->preload(),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (City $record, Tables\Actions\DeleteAction $action): void {
                        if ($record->destinations()->exists()) {
                            Notification::make()->warning()
                                ->title('Move or delete this city’s destination points first.')->send();
                            $action->cancel();
                        }
                    }),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [DestinationsRelationManager::class];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCities::route('/'),
            'create' => Pages\CreateCity::route('/create'),
            'edit' => Pages\EditCity::route('/{record}/edit'),
        ];
    }
}
