<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class UserPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'users';
    }

    /** Nobody can delete their own account (prevents locking the last admin out). */
    public function delete(User $user, Model $model): bool
    {
        return $user->isNot($model) && parent::delete($user, $model);
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $user->isNot($model) && parent::forceDelete($user, $model);
    }
}
