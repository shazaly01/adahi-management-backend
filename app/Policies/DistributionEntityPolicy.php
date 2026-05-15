<?php

namespace App\Policies;

use App\Models\DistributionEntity;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DistributionEntityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('distribution_entity.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DistributionEntity $distributionEntity): bool
    {
        return $user->hasPermissionTo('distribution_entity.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('distribution_entity.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DistributionEntity $distributionEntity): bool
    {
        return $user->hasPermissionTo('distribution_entity.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DistributionEntity $distributionEntity): bool
    {
        return $user->hasPermissionTo('distribution_entity.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DistributionEntity $distributionEntity): bool
    {
        return $user->hasPermissionTo('distribution_entity.delete');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DistributionEntity $distributionEntity): bool
    {
        return $user->hasPermissionTo('distribution_entity.delete');
    }
}
