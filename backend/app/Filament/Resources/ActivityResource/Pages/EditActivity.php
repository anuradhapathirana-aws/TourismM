<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

final class EditActivity extends EditRecord
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make()
                ->before(function (Actions\ForceDeleteAction $action): void {
                    if ($this->getRecord()->tourDays()->exists()) {
                        Notification::make()->warning()
                            ->title('This activity is used in tour itineraries. Remove it from those days first, or keep it deleted (it stays hidden).')->send();
                        $action->cancel();
                    }
                }),
        ];
    }
}
