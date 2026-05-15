<?php

namespace App\Policies;

use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BeneficiaryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('beneficiary.view');
    }

    public function view(User $user, Beneficiary $beneficiary): bool
    {
        if (!$user->hasPermissionTo('beneficiary.view')) {
            return false;
        }

        // إذا كان المستخدم موزعاً، يجب أن يكون هو من أضاف هذا المستفيد
        if ($user->hasRole('Distributor')) {
            return $user->id === $beneficiary->user_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('beneficiary.create');
    }

    public function update(User $user, Beneficiary $beneficiary): bool
    {
        if (!$user->hasPermissionTo('beneficiary.update')) {
            return false;
        }

        if ($user->hasRole('Distributor')) {
            return $user->id === $beneficiary->user_id;
        }

        return true;
    }

    public function delete(User $user, Beneficiary $beneficiary): bool
    {
        if (!$user->hasPermissionTo('beneficiary.delete')) {
            return false;
        }

        if ($user->hasRole('Distributor')) {
            return $user->id === $beneficiary->user_id;
        }

        return true;
    }
}
