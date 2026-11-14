<?php

declare(strict_types=1);

namespace App\Policies;

final class DistrictPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'districts';
    }
}
