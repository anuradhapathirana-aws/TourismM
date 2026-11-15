<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\TourStatus;
use App\Filament\Resources\TourResource\Pages;
use App\Filament\Resources\TourResource\TourForm;
use App\Models\Tour;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/** Tours: planned day by day with destination points, priced by group size. */
final class TourResource extends Resource
{
    protected static ?string $model = Tour::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Tours';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->columns(['lg' => 4])->schema(TourForm::schema());
    }

    public static function table(Table $table): Table
    {
        $currency = config('tours.currency');

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['category:id,name', 'media'])
                ->withCount('days')
                ->withMin('prices', 'price_per_person'))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('cover')->collection('cover')->conversion('thumb')->square()->label(''),
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category.name')->label('Category')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('days_count')->label('Duration')->sortable()
                    ->formatStateUsing(fn (int $state) => Tour::durationLabel($state) ?: '—'),
                Tables\Columns\TextColumn::make('prices_min_price_per_person')->label('From')->sortable()
                    ->formatStateUsing(fn ($state) => $state ? $currency.' '.number_format((float) $state) : '—'),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\IconColumn::make('is_featured')->label('Featured')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->relationship('category', 'name')->preload(),
                Tables\Filters\SelectFilter::make('status')->options(TourStatus::class),
                Tables\Filters\TernaryFilter::make('is_featured')->label('Featured'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                ...self::publishActions(),
                self::duplicateAction(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    /** What a tour still needs before it can be published (empty = ready). @return list<string> */
    public static function publishBlockers(Tour $tour): array
    {
        return array_values(array_filter([
            $tour->getFirstMedia('cover') ? null : 'a cover image',
            $tour->days()->exists() ? null : 'at least one itinerary day',
            $tour->prices()->exists() ? null : 'at least one price row',
        ]));
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
                ->visible(fn (Tour $record) => $record->status !== TourStatus::Published && ! $record->trashed())
                ->authorize(fn (Tour $record) => auth()->user()?->can('update', $record))
                ->action(function (Tour $record, Tables\Actions\Action $action): void {
                    if ($missing = self::publishBlockers($record)) {
                        Notification::make()->danger()->title('Add '.implode(', ', $missing).' before publishing.')->send();
                        $action->cancel();
                    }
                    $record->update(['status' => TourStatus::Published]);
                }),
            Tables\Actions\Action::make('unpublish')
                ->icon('heroicon-o-eye-slash')
                ->color('gray')
                ->iconButton()
                ->tooltip('Unpublish (back to draft)')
                ->visible(fn (Tour $record) => $record->status === TourStatus::Published && ! $record->trashed())
                ->authorize(fn (Tour $record) => auth()->user()?->can('update', $record))
                ->action(fn (Tour $record) => $record->update(['status' => TourStatus::Draft])),
        ];
    }

    public static function duplicateAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('duplicate')
            ->icon('heroicon-o-document-duplicate')
            ->color('gray')
            ->iconButton()
            ->tooltip('Duplicate (copy days, prices and photos as a new draft)')
            ->requiresConfirmation()
            ->modalHeading('Duplicate this tour?')
            ->modalDescription('A draft copy with the same days, points, prices and photos is created. You can then add or remove days.')
            ->visible(fn (Tour $record) => ! $record->trashed())
            ->authorize(fn () => auth()->user()?->can('create', Tour::class))
            ->action(function (Tour $record, Tables\Actions\Action $action): void {
                $copy = $record->duplicate();
                Notification::make()->success()->title('Draft copy created.')->send();
                $action->redirect(self::getUrl('edit', ['record' => $copy]));
            });
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'category.name'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTours::route('/'),
            'create' => Pages\CreateTour::route('/create'),
            'edit' => Pages\EditTour::route('/{record}/edit'),
        ];
    }
}
