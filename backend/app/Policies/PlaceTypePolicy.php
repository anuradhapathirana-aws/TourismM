<?php

declare(strict_types=1);

namespace App\Policies;

final class PlaceTypePolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'place-types';
    }
}
