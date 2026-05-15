<?php

namespace App\Policies;

use App\Models\Allocation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AllocationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('allocation.view');
    }

    public function view(User $user, Allocation $allocation): bool
    {
        return $user->hasPermissionTo('allocation.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('allocation.create');
    }

    public function update(User $user, Allocation $allocation): bool
    {
        return $user->hasPermissionTo('allocation.update');
    }

    public function delete(User $user, Allocation $allocation): bool
    {
        return $user->hasPermissionTo('allocation.delete');
    }

    public function restore(User $user, Allocation $allocation): bool
    {
        return $user->hasPermissionTo('allocation.delete');
    }

    public function forceDelete(User $user, Allocation $allocation): bool
    {
        return $user->hasPermissionTo('allocation.delete');
    }
}
