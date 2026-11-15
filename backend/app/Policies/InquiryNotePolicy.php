<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\InquiryNote;
use App\Models\User;

/**
 * Timeline rows follow the inquiry's permissions: whoever can view an inquiry sees its timeline,
 * whoever can update it may add notes. Rows are an audit trail, so nobody edits or deletes them.
 */
final class InquiryNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->can('inquiries.view');
    }

    public function view(User $user, InquiryNote $note): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->can('inquiries.update');
    }

    public function update(User $user, InquiryNote $note): bool
    {
        return false;
    }

    public function delete(User $user, InquiryNote $note): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
