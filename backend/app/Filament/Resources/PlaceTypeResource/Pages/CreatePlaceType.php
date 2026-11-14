<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlaceTypeResource\Pages;

use App\Filament\Resources\PlaceTypeResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePlaceType extends CreateRecord
{
    protected static string $resource = PlaceTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
