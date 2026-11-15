<?php

declare(strict_types=1);

namespace App\Filament\Resources\TourResource\Pages;

use App\Filament\Resources\TourResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTour extends CreateRecord
{
    protected static string $resource = TourResource::class;

    protected static ?string $title = 'Plan a tour';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
