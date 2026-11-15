<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Meals included on an itinerary day. */
enum Meal: string implements HasLabel
{
    case Breakfast = 'breakfast';
    case Lunch = 'lunch';
    case Dinner = 'dinner';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    /** B / L / D, as tour operators abbreviate it. */
    public function short(): string
    {
        return strtoupper($this->value[0]);
    }
}
