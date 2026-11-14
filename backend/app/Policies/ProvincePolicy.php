<?php

declare(strict_types=1);

namespace App\Policies;

final class ProvincePolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'provinces';
    }
}
