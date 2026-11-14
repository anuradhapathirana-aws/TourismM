<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\DistrictResource\Pages;
use App\Filament\Support\AdminUi;
use App\Filament\Support\FormRules;
use App\Models\District;
use App\Support\Validation\DistrictRules;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Reference data (seeded). Super Admin only by default. */
final class DistrictResource extends Resource
{
    protected static ?string $model = District::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        $rules = fn (?Model $record): array => DistrictRules::rules($record instanceof District ? $record : null);

        return $form->schema([
            Section::make('District')->schema([
                AdminUi::grid([
                    FormRules::sluggedName(TextInput::make('name')->required()->minLength(2)->maxLength(100)->rules(FormRules::for($rules, 'name')))->span('md'),
                    FormRules::slugInput($rules, 120)->span('md'),
                    Select::make('province_id')->label('Province')->relationship('province', 'name')->preload()->required()
                        ->rules(FormRules::for($rules, 'province_id'))->span('md'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('province:id,name')->withCount('cities'))
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('province.name')->label('Province')->sortable(),
                Tables\Columns\TextColumn::make('cities_count')->label('Cities')->badge(),
            ])
            ->filters([Tables\Filters\SelectFilter::make('province')->relationship('province', 'name')])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDistricts::route('/'),
            'create' => Pages\CreateDistrict::route('/create'),
            'edit' => Pages\EditDistrict::route('/{record}/edit'),
        ];
    }
}
