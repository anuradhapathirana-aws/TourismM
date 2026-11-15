<?php

declare(strict_types=1);

namespace App\Filament\Resources\TourCategoryResource\Pages;

use App\Filament\Resources\TourCategoryResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

final class EditTourCategory extends EditRecord
{
    protected static string $resource = TourCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make()
                ->before(function (Actions\ForceDeleteAction $action): void {
                    if ($this->getRecord()->tours()->withTrashed()->exists()) {
                        Notification::make()->warning()
                            ->title('This category still has tours. Move them to another category first.')->send();
                        $action->cancel();
                    }
                }),
        ];
    }
}
