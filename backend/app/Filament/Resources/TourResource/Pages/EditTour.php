<?php

declare(strict_types=1);

namespace App\Filament\Resources\TourResource\Pages;

use App\Filament\Resources\TourResource;
use App\Models\Tour;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

final class EditTour extends EditRecord
{
    protected static string $resource = TourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('duplicate')
                ->label('Duplicate')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('A draft copy with the same days, points, prices and photos is created.')
                ->authorize(fn () => auth()->user()?->can('create', Tour::class))
                ->action(function (Tour $record): void {
                    $copy = $record->duplicate();
                    Notification::make()->success()->title('Draft copy created.')->send();
                    $this->redirect(TourResource::getUrl('edit', ['record' => $copy]));
                }),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
        ];
    }
}
