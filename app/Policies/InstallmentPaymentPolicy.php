<?php

namespace App\Policies;

use App\Models\InstallmentPayment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstallmentPaymentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('installment.view');
    }

    public function view(User $user, InstallmentPayment $installmentPayment): bool
    {
        return $user->hasPermissionTo('installment.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('installment.collect');
    }

    /**
     * يُمنع التعديل على إيصالات الدفع بعد تسجيلها لأسباب محاسبية
     */
    public function update(User $user, InstallmentPayment $installmentPayment): bool
    {
        return false;
    }

    /**
     * يُمنع الحذف، يُفضل عمل تسوية أو حركة عكسية برمجياً في حال الخطأ
     */
    public function delete(User $user, InstallmentPayment $installmentPayment): bool
    {
        return false;
    }

    public function restore(User $user, InstallmentPayment $installmentPayment): bool
    {
        return false;
    }

    public function forceDelete(User $user, InstallmentPayment $installmentPayment): bool
    {
        return false;
    }
}
