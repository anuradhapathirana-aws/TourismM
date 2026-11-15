<?php

declare(strict_types=1);

namespace App\Filament\Resources\InquiryResource\Pages;

use App\Filament\Resources\InquiryResource;
use App\Filament\Resources\InquiryResource\InquiryActions;
use App\Models\Inquiry;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

/** The full request on one screen, with reply shortcuts and the timeline underneath. */
final class ViewInquiry extends ViewRecord
{
    protected static string $resource = InquiryResource::class;

    public function getTitle(): string
    {
        /** @var Inquiry $record */
        $record = $this->getRecord();

        return "{$record->reference} · {$record->guest?->name}";
    }

    /** Everything the page shows, loaded in a few queries (no N+1). */
    protected function resolveRecord(int|string $key): Model
    {
        /** @var Inquiry $record */
        $record = parent::resolveRecord($key);

        return $record->load([
            'guest' => fn ($query) => $query->withCount('inquiries'),
            'assignee:id,name',
            'destinations:id,name',
            'activities:id,name',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            InquiryActions::pageWhatsApp(),
            InquiryActions::pageEmail(),
            InquiryActions::pageAssignToMe(),
            InquiryActions::pageStatus(),
            Actions\EditAction::make()->color('gray'),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
