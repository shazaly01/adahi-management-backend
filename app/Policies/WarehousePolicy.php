<?php

namespace App\Policies;

use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class WarehousePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('warehouse.view');
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->hasPermissionTo('warehouse.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('warehouse.create');
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->hasPermissionTo('warehouse.update');
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->hasPermissionTo('warehouse.delete');
    }
}
