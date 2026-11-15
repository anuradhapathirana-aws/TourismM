<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/** Channel the inquiry came in through. Website = submitted online; the rest are entered by staff. */
enum InquirySource: string implements HasIcon, HasLabel
{
    case Website = 'website';
    case WhatsApp = 'whatsapp';
    case Phone = 'phone';
    case Email = 'email';
    case WalkIn = 'walk_in';

    public function getLabel(): string
    {
        return match ($this) {
            self::Website => 'Website',
            self::WhatsApp => 'WhatsApp',
            self::Phone => 'Phone',
            self::Email => 'Email',
            self::WalkIn => 'Walk-in',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Website => 'heroicon-m-globe-alt',
            self::WhatsApp => 'heroicon-m-chat-bubble-oval-left',
            self::Phone => 'heroicon-m-phone',
            self::Email => 'heroicon-m-envelope',
            self::WalkIn => 'heroicon-m-building-storefront',
        };
    }
}
