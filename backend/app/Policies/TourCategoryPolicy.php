<?php

declare(strict_types=1);

namespace App\Policies;

final class TourCategoryPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'tour-categories';
    }
}
