<?php

declare(strict_types=1);

namespace App\Policies;

final class TourPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'tours';
    }
}
