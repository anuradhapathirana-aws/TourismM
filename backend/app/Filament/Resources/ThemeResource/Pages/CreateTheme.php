<?php

declare(strict_types=1);

namespace App\Filament\Resources\ThemeResource\Pages;

use App\Filament\Resources\ThemeResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTheme extends CreateRecord
{
    protected static string $resource = ThemeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
