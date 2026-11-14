<?php

declare(strict_types=1);

namespace App\Policies;

final class DestinationPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'destinations';
    }
}
