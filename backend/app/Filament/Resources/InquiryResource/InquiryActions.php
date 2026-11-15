<?php

declare(strict_types=1);

namespace App\Filament\Resources\InquiryResource;

use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use App\Support\Validation\InquiryRules;
use Filament\Actions\Action as PageAction;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action as TableAction;

/**
 * Quick actions used on the list (row icon buttons) and on the inquiry page (header buttons).
 * Status changes go through the model, so the timeline row is written in one place.
 */
final class InquiryActions
{
    /** @return list<Component> */
    public static function statusForm(): array
    {
        $rules = InquiryRules::statusChange();

        return [
            ToggleButtons::make('status')->options(InquiryStatus::class)->inline()->required()->live()
                ->rules($rules['status']),
            TextInput::make('quoted_amount')->label('Quoted total')->numeric()->minValue(0)->maxValue(1000000)
                ->prefix(config('tours.currency'))
                ->visible(fn (Get $get) => in_array($get('status'), [InquiryStatus::Quoted->value, InquiryStatus::Booked->value], true))
                ->rules($rules['quoted_amount']),
            Textarea::make('note')->rows(3)->maxLength(InquiryRules::NOTE_MAX)
                ->placeholder('e.g. Sent a 12-day itinerary by WhatsApp, waiting for the guest to confirm dates.')
                ->rules($rules['note']),
        ];
    }

    /** @param array{status: string|InquiryStatus, quoted_amount?: mixed, note?: ?string} $data */
    public static function changeStatus(Inquiry $inquiry, array $data): void
    {
        $status = $data['status'] instanceof InquiryStatus ? $data['status'] : InquiryStatus::from($data['status']);
        $note = filled($data['note'] ?? null) ? (string) $data['note'] : null;
        $changes = ['status' => $status];
        if (array_key_exists('quoted_amount', $data) && $data['quoted_amount'] !== null && $data['quoted_amount'] !== '') {
            $changes['quoted_amount'] = $data['quoted_amount'];
        }

        if ($status === $inquiry->status) {
            $inquiry->update($changes);
            if ($note) {
                $inquiry->timeline()->create(['user_id' => auth()->id(), 'body' => $note]);
            }
        } else {
            $inquiry->statusNote = $note;
            $inquiry->update($changes);
        }

        Notification::make()->success()->title("Status: {$status->getLabel()}")->send();
    }

    public static function assignToMe(Inquiry $inquiry): void
    {
        $inquiry->update(['assigned_to' => auth()->id()]);
        $inquiry->timeline()->create(['user_id' => auth()->id(), 'body' => 'Took ownership of this inquiry.']);
        Notification::make()->success()->title('Assigned to you.')->send();
    }

    public static function tableStatus(): TableAction
    {
        return TableAction::make('changeStatus')
            ->icon('heroicon-o-arrow-path-rounded-square')->color('gray')
            ->iconButton()->tooltip('Change status / add note')
            ->modalHeading(fn (Inquiry $record) => "Update {$record->reference}")
            ->modalWidth('lg')
            ->fillForm(fn (Inquiry $record) => ['status' => $record->status, 'quoted_amount' => $record->quoted_amount])
            ->form(self::statusForm())
            ->visible(fn (Inquiry $record) => ! $record->trashed())
            ->authorize(fn (Inquiry $record) => auth()->user()?->can('update', $record))
            ->action(fn (Inquiry $record, array $data) => self::changeStatus($record, $data));
    }

    public static function pageStatus(): PageAction
    {
        return PageAction::make('changeStatus')->label('Change status')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->modalHeading(fn (Inquiry $record) => "Update {$record->reference}")
            ->modalWidth('lg')
            ->fillForm(fn (Inquiry $record) => ['status' => $record->status, 'quoted_amount' => $record->quoted_amount])
            ->form(self::statusForm())
            ->visible(fn (Inquiry $record) => ! $record->trashed())
            ->authorize(fn (Inquiry $record) => auth()->user()?->can('update', $record))
            ->action(fn (Inquiry $record, array $data) => self::changeStatus($record, $data));
    }

    public static function pageAssignToMe(): PageAction
    {
        return PageAction::make('assignToMe')->label('Assign to me')
            ->icon('heroicon-o-user-plus')->color('gray')
            ->visible(fn (Inquiry $record) => ! $record->trashed() && $record->assigned_to !== auth()->id())
            ->authorize(fn (Inquiry $record) => auth()->user()?->can('update', $record))
            ->action(fn (Inquiry $record) => self::assignToMe($record));
    }

    public static function pageWhatsApp(): PageAction
    {
        return PageAction::make('whatsapp')->label('WhatsApp')
            ->icon('heroicon-o-chat-bubble-oval-left')->color('success')
            ->url(fn (Inquiry $record) => self::whatsappUrl($record), shouldOpenInNewTab: true)
            ->visible(fn (Inquiry $record) => $record->guest?->whatsappUrl() !== null);
    }

    public static function pageEmail(): PageAction
    {
        return PageAction::make('email')->label('Email')
            ->icon('heroicon-o-envelope')->color('gray')
            ->url(fn (Inquiry $record) => 'mailto:'.$record->guest?->email.'?subject='.rawurlencode("Your Sri Lanka journey ({$record->reference})"))
            ->visible(fn (Inquiry $record) => filled($record->guest?->email));
    }

    /** WhatsApp chat with a friendly first line that quotes the reference. */
    public static function whatsappUrl(Inquiry $inquiry): ?string
    {
        $base = $inquiry->guest?->whatsappUrl();
        if (! $base) {
            return null;
        }
        $first = strtok((string) $inquiry->guest->name, ' ');

        return $base.'?text='.rawurlencode("Hello {$first}, thank you for your Sri Lanka journey request ({$inquiry->reference}). ");
    }
}
