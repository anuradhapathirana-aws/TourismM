<?php

declare(strict_types=1);

namespace App\Policies;

final class ThemePolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'themes';
    }
}
