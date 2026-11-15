<?php

declare(strict_types=1);

namespace App\Filament\Resources\TourCategoryResource\Pages;

use App\Filament\Resources\TourCategoryResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTourCategory extends CreateRecord
{
    protected static string $resource = TourCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
