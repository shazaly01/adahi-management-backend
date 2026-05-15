<?php

namespace App\Policies;

use App\Models\SacrificeType;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SacrificeTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('sacrifice_type.view');
    }

    public function view(User $user, SacrificeType $sacrificeType): bool
    {
        return $user->hasPermissionTo('sacrifice_type.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('sacrifice_type.create');
    }

    public function update(User $user, SacrificeType $sacrificeType): bool
    {
        return $user->hasPermissionTo('sacrifice_type.update');
    }

    public function delete(User $user, SacrificeType $sacrificeType): bool
    {
        return $user->hasPermissionTo('sacrifice_type.delete');
    }

    public function restore(User $user, SacrificeType $sacrificeType): bool
    {
        return $user->hasPermissionTo('sacrifice_type.delete');
    }

    public function forceDelete(User $user, SacrificeType $sacrificeType): bool
    {
        return $user->hasPermissionTo('sacrifice_type.delete');
    }
}
