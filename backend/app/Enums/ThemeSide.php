<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Which column of the home-page explore map the theme card sits in (desktop). */
enum ThemeSide: string implements HasLabel
{
    case Left = 'left';
    case Right = 'right';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }
}
