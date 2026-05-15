<?php

namespace App\Providers;

// النماذج (Models)
use App\Models\User;
use App\Models\Beneficiary;
use App\Models\InKindAssistance;
use App\Models\FinancialAssistance;
use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use Spatie\Permission\Models\Role;

// السياسات (Policies)
use App\Policies\UserPolicy;
use App\Policies\RolePolicy;
use App\Policies\BeneficiaryPolicy;
use App\Policies\InKindAssistancePolicy;
use App\Policies\FinancialAssistancePolicy;
use App\Policies\TreasuryPolicy;
use App\Policies\TreasuryTransactionPolicy;
// أضف هذا في قسم النماذج (Models)
use App\Models\Area;
// أضف هذا في قسم السياسات (Policies)
use App\Policies\AreaPolicy;
use App\Models\Message;
use App\Policies\MessagePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * رسم خارطة النماذج والسياسات الخاصة بها.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // إدارة المستخدمين والأدوار
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,

        // نظام المساعدات والخزينة الجديد
        Area::class        => AreaPolicy::class,
        Message::class             => MessagePolicy::class,
        Beneficiary::class         => BeneficiaryPolicy::class,
        InKindAssistance::class     => InKindAssistancePolicy::class,
        FinancialAssistance::class  => FinancialAssistancePolicy::class,
        Treasury::class             => TreasuryPolicy::class,
        TreasuryTransaction::class  => TreasuryTransactionPolicy::class,
        Message::class             => MessagePolicy::class,
    ];

    /**
     * تسجيل أي خدمات مصادقة أو تصريح.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // منح الـ Super Admin صلاحية الوصول الكامل لكل شيء دون فحص الصلاحيات
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
