<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\DestinationStatus;
use App\Filament\Resources\DestinationResource\DestinationForm;
use App\Filament\Resources\DestinationResource\Pages;
use App\Models\Destination;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class DestinationResource extends Resource
{
    protected static ?string $model = Destination::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Locations';

    protected static ?string $navigationLabel = 'Destination points';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(['lg' => 4])
            ->schema(DestinationForm::schema(fn (Get $get): ?int => filled($get('city_id')) ? (int) $get('city_id') : null));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['city:id,name', 'placeType:id,name', 'media']))
            ->defaultSort('updated_at', 'desc')
            ->columns(self::columns(withCity: true))
            ->filters([
                Tables\Filters\SelectFilter::make('city')->relationship('city', 'name')->searchable()->preload(),
                Tables\Filters\SelectFilter::make('placeType')->label('Type')->relationship('placeType', 'name')->preload(),
                Tables\Filters\SelectFilter::make('themes')->relationship('themes', 'title')->multiple()->preload(),
                Tables\Filters\SelectFilter::make('status')->options(DestinationStatus::class),
                Tables\Filters\TernaryFilter::make('is_featured')->label('Featured'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions(self::rowActions())
            ->bulkActions(self::bulkActions());
    }

    /** @return list<Tables\Columns\Column> */
    public static function columns(bool $withCity): array
    {
        return array_values(array_filter([
            Tables\Columns\SpatieMediaLibraryImageColumn::make('cover')
                ->collection('cover')->conversion('thumb')->square()->label(''),
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('placeType.name')->label('Type')->toggleable(),
            $withCity ? Tables\Columns\TextColumn::make('city.name')->label('City')->sortable()->searchable() : null,
            Tables\Columns\TextColumn::make('latitude')->label('Lat / Lng')
                ->formatStateUsing(fn (Destination $r) => number_format($r->latitude, 4).', '.number_format($r->longitude, 4))
                ->url(fn (Destination $r) => "https://www.google.com/maps?q={$r->latitude},{$r->longitude}", shouldOpenInNewTab: true)
                ->toggleable(),
            Tables\Columns\TextColumn::make('status')->badge()->sortable(),
            Tables\Columns\IconColumn::make('is_featured')->label('Featured')->boolean()->sortable(),
            Tables\Columns\TextColumn::make('updated_at')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ]));
    }

    /**
     * Icon-only row actions (Edit/Delete/Restore are made icon buttons globally by AdminUi).
     *
     * @return list<Tables\Actions\Action>
     */
    public static function rowActions(): array
    {
        return [
            Tables\Actions\EditAction::make(),
            ...self::publishActions(),
            Tables\Actions\DeleteAction::make(),
            Tables\Actions\RestoreAction::make(),
            Tables\Actions\ForceDeleteAction::make(),
        ];
    }

    /** @return list<Tables\Actions\Action> */
    public static function publishActions(): array
    {
        return [
            Tables\Actions\Action::make('publish')
                ->icon('heroicon-o-eye')
                ->color('success')
                ->iconButton()
                ->tooltip('Publish')
                ->visible(fn (Destination $record) => $record->status !== DestinationStatus::Published && ! $record->trashed())
                ->authorize(fn (Destination $record) => auth()->user()?->can('update', $record))
                ->action(function (Destination $record, Tables\Actions\Action $action): void {
                    if (! $record->getFirstMedia('cover')) {
                        Notification::make()->danger()
                            ->title('Add a cover image before publishing.')->send();
                        $action->cancel();
                    }
                    $record->update(['status' => DestinationStatus::Published]);
                }),
            Tables\Actions\Action::make('unpublish')
                ->icon('heroicon-o-eye-slash')
                ->color('gray')
                ->iconButton()
                ->tooltip('Unpublish (back to draft)')
                ->visible(fn (Destination $record) => $record->status === DestinationStatus::Published && ! $record->trashed())
                ->authorize(fn (Destination $record) => auth()->user()?->can('update', $record))
                ->action(fn (Destination $record) => $record->update(['status' => DestinationStatus::Draft])),
        ];
    }

    /** @return list<Tables\Actions\BulkActionGroup> */
    public static function bulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
            ]),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'city.name'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDestinations::route('/'),
            'create' => Pages\CreateDestination::route('/create'),
            'edit' => Pages\EditDestination::route('/{record}/edit'),
        ];
    }
}
