<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlaceTypeResource\Pages;

use App\Filament\Resources\PlaceTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EditPlaceType extends EditRecord
{
    protected static string $resource = PlaceTypeResource::class;

    protected function getHeaderActions(): array
    {
        $softDeletes = in_array(SoftDeletes::class, class_uses_recursive($this->getRecord()), true);

        return $softDeletes
            ? [Actions\DeleteAction::make(), Actions\RestoreAction::make(), Actions\ForceDeleteAction::make()]
            : [Actions\DeleteAction::make()];
    }
}
