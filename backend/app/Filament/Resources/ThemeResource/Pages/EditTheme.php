<?php

declare(strict_types=1);

namespace App\Filament\Resources\ThemeResource\Pages;

use App\Filament\Resources\ThemeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EditTheme extends EditRecord
{
    protected static string $resource = ThemeResource::class;

    protected function getHeaderActions(): array
    {
        $softDeletes = in_array(SoftDeletes::class, class_uses_recursive($this->getRecord()), true);

        return $softDeletes
            ? [Actions\DeleteAction::make(), Actions\RestoreAction::make(), Actions\ForceDeleteAction::make()]
            : [Actions\DeleteAction::make()];
    }
}
