<?php

declare(strict_types=1);

namespace App\Filament\Resources\InquiryResource\Pages;

use App\Enums\InquiryType;
use App\Filament\Resources\InquiryResource;
use App\Services\InquiryIntake;
use Filament\Resources\Pages\CreateRecord;

/** An inquiry taken by phone, WhatsApp, email or in person. ?guest=ID preselects the guest. */
final class CreateInquiry extends CreateRecord
{
    protected static string $resource = InquiryResource::class;

    /** @var array{destination_ids: list<int>, activity_ids: list<int>} */
    private array $picks = ['destination_ids' => [], 'activity_ids' => []];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->picks = [
            'destination_ids' => array_values($data['destination_ids'] ?? []),
            'activity_ids' => array_values($data['activity_ids'] ?? []),
        ];
        unset($data['destination_ids'], $data['activity_ids']);

        $data['type'] = InquiryType::CustomTour;
        $data['child_ages'] = (int) ($data['children'] ?? 0) > 0 ? array_map('intval', array_values($data['child_ages'] ?? [])) : null;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->destinations()->sync(InquiryIntake::ordered($this->picks['destination_ids']));
        $this->record->activities()->sync(InquiryIntake::ordered($this->picks['activity_ids']));
    }

    protected function getRedirectUrl(): string
    {
        return InquiryResource::getUrl('view', ['record' => $this->record]);
    }
}
