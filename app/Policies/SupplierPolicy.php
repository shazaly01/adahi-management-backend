<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('supplier.view');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->hasPermissionTo('supplier.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('supplier.create');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->hasPermissionTo('supplier.update');
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->hasPermissionTo('supplier.delete');
    }
}
