<?php

declare(strict_types=1);

namespace App\Policies;

final class InquiryPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'inquiries';
    }
}
