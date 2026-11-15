<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestResource\Pages;

use App\Filament\Resources\GuestResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateGuest extends CreateRecord
{
    protected static string $resource = GuestResource::class;

    protected function getRedirectUrl(): string
    {
        return GuestResource::getUrl('edit', ['record' => $this->record]);
    }
}
