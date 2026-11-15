<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** How demanding the tour is: helps travellers pick the right trip. */
enum TourPace: string implements HasLabel
{
    case Relaxed = 'relaxed';
    case Moderate = 'moderate';
    case Active = 'active';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }
}
