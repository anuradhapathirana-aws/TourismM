<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestResource\Pages;

use App\Filament\Resources\GuestResource;
use App\Filament\Resources\InquiryResource;
use App\Models\Guest;
use App\Models\Inquiry;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

final class EditGuest extends EditRecord
{
    protected static string $resource = GuestResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Guest $guest */
        $guest = $this->getRecord();

        return [
            Actions\Action::make('whatsapp')->label('WhatsApp')->icon('heroicon-o-chat-bubble-oval-left')->color('success')
                ->url($guest->whatsappUrl(), shouldOpenInNewTab: true)
                ->visible($guest->whatsappUrl() !== null),
            Actions\Action::make('newInquiry')->label('New inquiry')->icon('heroicon-o-plus')
                ->url(InquiryResource::getUrl('create', ['guest' => $guest->getKey()]))
                ->visible(fn () => ! $guest->trashed() && auth()->user()?->can('create', Inquiry::class)),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make()
                ->before(function (Actions\ForceDeleteAction $action) use ($guest): void {
                    if ($guest->inquiries()->withTrashed()->exists()) {
                        Notification::make()->warning()
                            ->title('This guest has inquiries. Keep the guest deleted (hidden), or remove their inquiries permanently first.')->send();
                        $action->cancel();
                    }
                }),
        ];
    }
}
