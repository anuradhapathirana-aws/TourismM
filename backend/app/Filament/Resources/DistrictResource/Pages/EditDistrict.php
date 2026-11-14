<?php

declare(strict_types=1);

namespace App\Filament\Resources\DistrictResource\Pages;

use App\Filament\Resources\DistrictResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EditDistrict extends EditRecord
{
    protected static string $resource = DistrictResource::class;

    protected function getHeaderActions(): array
    {
        $softDeletes = in_array(SoftDeletes::class, class_uses_recursive($this->getRecord()), true);

        return $softDeletes
            ? [Actions\DeleteAction::make(), Actions\RestoreAction::make(), Actions\ForceDeleteAction::make()]
            : [Actions\DeleteAction::make()];
    }
}
