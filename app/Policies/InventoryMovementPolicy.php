<?php

namespace App\Policies;

use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InventoryMovementPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('inventory.view');
    }

    public function view(User $user, InventoryMovement $inventoryMovement): bool
    {
        return $user->hasPermissionTo('inventory.view');
    }

    /**
     * الحركات المخزنية تنشأ برمجياً عبر الـ Services فقط لحماية النظام المالي
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * يُمنع التعديل على الحركات المخزنية نهائياً (Ledger Immutability)
     */
    public function update(User $user, InventoryMovement $inventoryMovement): bool
    {
        return false;
    }

    /**
     * يُمنع حذف الحركات المخزنية نهائياً
     */
    public function delete(User $user, InventoryMovement $inventoryMovement): bool
    {
        return false;
    }

    public function restore(User $user, InventoryMovement $inventoryMovement): bool
    {
        return false;
    }

    public function forceDelete(User $user, InventoryMovement $inventoryMovement): bool
    {
        return false;
    }
}
