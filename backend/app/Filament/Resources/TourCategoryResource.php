<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TourCategoryResource\Pages;
use App\Filament\Support\AdminUi;
use App\Filament\Support\FormRules;
use App\Filament\Support\ImageFields;
use App\Models\TourCategory;
use App\Support\Validation\TourCategoryRules;
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

/** How trips are classified (Beach Tours, Cultural Tours …). One category has many tours. */
final class TourCategoryResource extends Resource
{
    protected static ?string $model = TourCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Tours';

    protected static ?string $navigationLabel = 'Tour categories';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        $rules = fn (?Model $record, Get $get): array => TourCategoryRules::rules($record instanceof TourCategory ? $record : null);

        return $form->columns(['lg' => 4])->schema([
            Section::make('Tour category')->columnSpan(['lg' => 3])->schema([
                AdminUi::grid([
                    FormRules::sluggedName(
                        TextInput::make('name')->required()->minLength(2)->maxLength(80)->rules(FormRules::for($rules, 'name'))
                    )->span('md'),
                    FormRules::slugInput($rules, 100)->span('md'),
                    TextInput::make('icon')
                        ->label('Icon')
                        ->prefix('bi-')
                        ->placeholder('umbrella')
                        ->maxLength(60)
                        ->livePattern(TourCategoryRules::ICON_REGEX, 'Use the icon name only: lowercase letters, numbers and hyphens (e.g. umbrella).')
                        ->tip('Optional Bootstrap icon name, e.g. umbrella. Browse names at icons.getbootstrap.com.')
                        ->rules(FormRules::for($rules, 'icon'))
                        ->span('sm'),
                    TextInput::make('sort_order')->label('Sort')->integer()->default(0)->minValue(0)->maxValue(65535)
                        ->tip('Lower numbers come first.')
                        ->rules(FormRules::for($rules, 'sort_order'))->span('xs'),
                    Toggle::make('is_active')->label('Active')->default(true)
                        ->tip('Inactive categories are hidden from the website.')
                        ->rules(FormRules::for($rules, 'is_active'))->span('xs'),
                    Textarea::make('summary')
                        ->label('Short description')
                        ->rows(2)
                        ->maxLength(300)
                        ->tip('1–2 sentences for the category card on the website.')
                        ->rules(FormRules::for($rules, 'summary'))
                        ->span('full'),
                ]),
            ]),
            Section::make('Cover image')->columnSpan(['lg' => 1])->schema([ImageFields::single('image', 'Image')]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('media')->withCount('tours'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('image')->collection('image')->conversion('thumb')->circular()->label(''),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('icon')->formatStateUsing(fn (?string $state) => $state ? "bi-{$state}" : '—'),
                Tables\Columns\TextColumn::make('summary')->label('Short description')->limit(60)
                    ->tooltip(fn (TourCategory $record) => $record->summary)->toggleable(),
                Tables\Columns\TextColumn::make('tours_count')->label('Tours')->badge(),
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
            'index' => Pages\ListTourCategories::route('/'),
            'create' => Pages\CreateTourCategory::route('/create'),
            'edit' => Pages\EditTourCategory::route('/{record}/edit'),
        ];
    }
}
