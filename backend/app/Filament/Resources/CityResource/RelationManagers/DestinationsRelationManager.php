<?php

declare(strict_types=1);

namespace App\Filament\Resources\CityResource\RelationManagers;

use App\Filament\Resources\DestinationResource;
use App\Filament\Resources\DestinationResource\DestinationForm;
use App\Models\Destination;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/** City edit page → "Destination points" tab: manage every point inside this city. */
final class DestinationsRelationManager extends RelationManager
{
    protected static string $relationship = 'destinations';

    protected static ?string $title = 'Destination points';

    protected static ?string $icon = 'heroicon-o-map-pin';

    public function form(Form $form): Form
    {
        $cityId = (int) $this->getOwnerRecord()->getKey();

        return $form->columns(['lg' => 4])->schema(DestinationForm::schema(fn () => $cityId, withCitySelect: false));
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScopes([SoftDeletingScope::class])
                ->with(['placeType:id,name', 'media']))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns(DestinationResource::columns(withCity: false))
            ->filters([Tables\Filters\TrashedFilter::make()])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Add point')->slideOver()->modalWidth('7xl'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->slideOver()->modalWidth('7xl'),
                Tables\Actions\Action::make('open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->iconButton()
                    ->tooltip('Open full page')
                    ->url(fn (Destination $record) => DestinationResource::getUrl('edit', ['record' => $record])),
                ...DestinationResource::publishActions(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions(DestinationResource::bulkActions());
    }
}
