<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * What the guest asked for. One inbox for every kind of inquiry:
 *  - CustomTour: "Build your own journey" (website popup or entered by staff)
 *  - Package: an inquiry about one published tour (tour_id set). Reserved for the website's tour enquiry form.
 */
enum InquiryType: string implements HasLabel
{
    case CustomTour = 'custom_tour';
    case Package = 'package';

    public function getLabel(): string
    {
        return match ($this) {
            self::CustomTour => 'Custom tour',
            self::Package => 'Tour package',
        };
    }
}
