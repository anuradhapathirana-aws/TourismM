<?php

declare(strict_types=1);

namespace App\Filament\Resources\InquiryResource\Pages;

use App\Filament\Resources\InquiryResource;
use App\Services\InquiryIntake;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/** Correct a request after talking to the guest. Status changes made here are logged on the timeline. */
final class EditInquiry extends EditRecord
{
    protected static string $resource = InquiryResource::class;

    /** @var array{destination_ids: list<int>, activity_ids: list<int>} */
    private array $picks = ['destination_ids' => [], 'activity_ids' => []];

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing(['destinations:id', 'activities:id']);
        $data['destination_ids'] = $this->record->destinations->pluck('id')->all();
        $data['activity_ids'] = $this->record->activities->pluck('id')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->picks = [
            'destination_ids' => array_values($data['destination_ids'] ?? []),
            'activity_ids' => array_values($data['activity_ids'] ?? []),
        ];
        unset($data['destination_ids'], $data['activity_ids']);

        $data['child_ages'] = (int) ($data['children'] ?? 0) > 0 ? array_map('intval', array_values($data['child_ages'] ?? [])) : null;
        if (($data['accommodation'] ?? null) === 'none') {
            $data['rooms'] = null;
            $data['meal_plan'] = null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->destinations()->sync(InquiryIntake::ordered($this->picks['destination_ids']));
        $this->record->activities()->sync(InquiryIntake::ordered($this->picks['activity_ids']));
    }

    protected function getRedirectUrl(): string
    {
        return InquiryResource::getUrl('view', ['record' => $this->record]);
    }
}
