<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/** Sales pipeline of an inquiry: New → Contacted → Quoted → Booked, or Lost at any point. */
enum InquiryStatus: string implements HasColor, HasIcon, HasLabel
{
    case New = 'new';
    case Contacted = 'contacted';
    case Quoted = 'quoted';
    case Booked = 'booked';
    case Lost = 'lost';

    /** Still being worked on (shown in the "open" counters). @return list<self> */
    public static function open(): array
    {
        return [self::New, self::Contacted, self::Quoted];
    }

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Contacted => 'warning',
            self::Quoted => 'primary',
            self::Booked => 'success',
            self::Lost => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::New => 'heroicon-m-sparkles',
            self::Contacted => 'heroicon-m-chat-bubble-left-right',
            self::Quoted => 'heroicon-m-document-currency-dollar',
            self::Booked => 'heroicon-m-check-badge',
            self::Lost => 'heroicon-m-x-circle',
        };
    }
}
