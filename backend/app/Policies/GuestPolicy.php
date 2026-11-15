<?php

declare(strict_types=1);

namespace App\Policies;

final class GuestPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'guests';
    }
}
