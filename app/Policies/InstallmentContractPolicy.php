<?php

namespace App\Policies;

use App\Models\InstallmentContract;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstallmentContractPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('installment.view');
    }

    public function view(User $user, InstallmentContract $installmentContract): bool
    {
        return $user->hasPermissionTo('installment.view');
    }

    /**
     * العقود تنشأ برمجياً فقط عبر الـ Service عند التوزيع بنظام الأقساط
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * التعديل يتم برمجياً لتحديث المبالغ المدفوعة والحالة
     */
    public function update(User $user, InstallmentContract $installmentContract): bool
    {
        return false;
    }

    public function delete(User $user, InstallmentContract $installmentContract): bool
    {
        return false;
    }

    public function restore(User $user, InstallmentContract $installmentContract): bool
    {
        return false;
    }

    public function forceDelete(User $user, InstallmentContract $installmentContract): bool
    {
        return false;
    }
}
