<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. إعادة تعيين ذاكرة الصلاحيات المؤقتة لضمان تطبيق التغييرات فوراً
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guardName = 'api';

        // 2. قائمة شاملة بكافة الصلاحيات المطلوبة للنظام
        $permissions = [
            'dashboard.view',

            // إدارة المستخدمين
            'user.view', 'user.create', 'user.update', 'user.delete',
            'role.view', 'role.create', 'role.update', 'role.delete',

            'area.view', 'area.create', 'area.update', 'area.delete',

            'message.view', 'message.create', 'message.delete',

            // النسخ الاحتياطي والإعدادات
            'backup.view', 'backup.create', 'backup.delete', 'backup.download',
            'setting.view', 'setting.update',

            // ---------------------------------------------------------
            // --- الصلاحيات الخاصة بنظام إدارة وتوزيع الأضاحي ---
            // ---------------------------------------------------------

            // جهات التوزيع (الكيان الجديد)
            'distribution_entity.view', 'distribution_entity.create', 'distribution_entity.update', 'distribution_entity.delete',

            // الموردون
            'supplier.view', 'supplier.create', 'supplier.update', 'supplier.delete',

            // المخازن
            'warehouse.view', 'warehouse.create', 'warehouse.update', 'warehouse.delete',

            // المستفيدون
            'beneficiary.view', 'beneficiary.create', 'beneficiary.update', 'beneficiary.delete',

            // أنواع الأضاحي
            'sacrifice_type.view', 'sacrifice_type.create', 'sacrifice_type.update', 'sacrifice_type.delete',

            // التوريد (الاستلام من الموردين)
            'supply.view', 'supply.create', 'supply.update', 'supply.delete',

            // العهد (تسليم الأضاحي للجهات الموزعة)
            'allocation.view', 'allocation.create', 'allocation.update', 'allocation.delete',

            // التوزيع (للمستفيدين النهائيين)
            'distribution.view', 'distribution.create', 'distribution.update', 'distribution.delete',
            'distribution.deliver', // تمت إضافة صلاحية شاشة التأكيد والتسليم

            // الأقساط
            'installment.view', 'installment.collect',

            // المخزون والأرصدة
            'inventory.view',

            // التقارير (الصلاحية الجديدة)
            'report.view',

            // صلاحيات التوزيع المخصصة (تُمنح للموزع حسب الشاشة)
            'distribute.free',          // معفى
            'distribute.cash',          // كاش
            'distribute.installments',  // أقساط
            'price.edit',               // السماح بتعديل السعر
        ];

        // إنشاء الصلاحيات في قاعدة البيانات (باستخدام firstOrCreate لتجنب التكرار)
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guardName]);
        }

        // --- 3. إنشاء الأدوار وتوزيع الصلاحيات بدقة ---

        // أ. دور Super Admin (يملك كل شيء عبر Gate::before فلا داعي لمنحه صلاحيات يدوياً)
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => $guardName]);

        // ب. دور Admin (مدير النظام - يملك كل الصلاحيات المسجلة)
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => $guardName]);
        $adminRole->givePermissionTo(Permission::all());

        // ج. دور Data Entry (مدخل بيانات)
        $dataEntryRole = Role::firstOrCreate(['name' => 'Data Entry', 'guard_name' => $guardName]);
        $dataEntryRole->givePermissionTo([
            'dashboard.view',
            'message.view', 'message.create',
        ]);

        // د. دور Auditor (المراجع) - يرى كل شيء ولا يغير شيئاً
        $auditorRole = Role::firstOrCreate(['name' => 'Auditor', 'guard_name' => $guardName]);
        $viewPermissions = Permission::where('name', 'like', '%.view')->pluck('name');
        $auditorRole->givePermissionTo($viewPermissions);

        // هـ. دور Distributor (الجهة الموزعة)
        $distributorRole = Role::firstOrCreate(['name' => 'Distributor', 'guard_name' => $guardName]);
        $distributorRole->givePermissionTo([
            'dashboard.view',
            'beneficiary.view', 'beneficiary.create',
            'distribution.view', 'distribution.create',
            'distribution.deliver', // تم منح الصلاحية لدور الموزع ليتمكن من رؤية شاشة التسليم وتأكيدها
            'installment.view', 'installment.collect',
        ]);
    }
}
