<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Permission-based policy. Subclasses only declare the permission prefix, e.g. "cities"
 * → cities.view / cities.create / cities.update / cities.delete / cities.restore / cities.force-delete.
 */
abstract class ResourcePolicy
{
    abstract protected function resource(): string;

    protected function allows(User $user, string $action): bool
    {
        return $user->is_active && $user->can("{$this->resource()}.{$action}");
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'view');
    }

    public function view(User $user, Model $model): bool
    {
        return $this->allows($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'create');
    }

    public function update(User $user, Model $model): bool
    {
        return $this->allows($user, 'update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->allows($user, 'delete');
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, 'delete');
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->allows($user, 'restore');
    }

    public function restoreAny(User $user): bool
    {
        return $this->allows($user, 'restore');
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $this->allows($user, 'force-delete');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->allows($user, 'force-delete');
    }

    public function reorder(User $user): bool
    {
        return $this->allows($user, 'update');
    }
}
