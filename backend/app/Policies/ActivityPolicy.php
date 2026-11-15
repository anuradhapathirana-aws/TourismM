<?php

declare(strict_types=1);

namespace App\Policies;

final class ActivityPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'activities';
    }
}
