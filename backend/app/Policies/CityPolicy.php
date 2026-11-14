<?php

declare(strict_types=1);

namespace App\Policies;

final class CityPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'cities';
    }
}
