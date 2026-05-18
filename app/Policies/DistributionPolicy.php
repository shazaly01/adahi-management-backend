<?php

namespace App\Policies;

use App\Models\Distribution;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DistributionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('distribution.view');
    }

    public function view(User $user, Distribution $distribution): bool
    {
        return $user->hasPermissionTo('distribution.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('distribution.create');
    }

    public function update(User $user, Distribution $distribution): bool
    {
        return $user->hasPermissionTo('distribution.update');
    }

    public function delete(User $user, Distribution $distribution): bool
    {
        return $user->hasPermissionTo('distribution.delete');
    }

    public function restore(User $user, Distribution $distribution): bool
    {
        return $user->hasPermissionTo('distribution.delete');
    }

    public function forceDelete(User $user, Distribution $distribution): bool
    {
        return $user->hasPermissionTo('distribution.delete');
    }

    // تمت إضافة الدالة الجديدة للتحقق من صلاحية الدخول لشاشة التسليم وتأكيدها
    public function deliver(User $user): bool
    {
        return $user->hasPermissionTo('distribution.deliver');
    }
}
