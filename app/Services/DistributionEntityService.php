<?php

namespace App\Services;

use App\Models\DistributionEntity;
use App\Models\EntityStock;
use App\Models\SacrificeType;
use Illuminate\Support\Facades\DB;
use Exception;

class DistributionEntityService
{
    /**
     * إنشاء جهة توزيع جديدة مع فتح مخازن صفرية استباقياً
     */
    public function createEntity(array $data): DistributionEntity
    {
        return DB::transaction(function () use ($data) {
            // 1. إنشاء جهة التوزيع (تمت إزالة id ليتم توليده تلقائياً)
            $entity = DistributionEntity::create([
                'name' => $data['name'],
                'region' => $data['region'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            // 2. جلب كافة أنواع الأضاحي المسجلة في النظام
            $sacrificeTypes = SacrificeType::pluck('id');

            // 3. تجهيز مصفوفة المخازن الصفرية
            $stocks = [];
            foreach ($sacrificeTypes as $typeId) {
                $stocks[] = [
                    'distribution_entity_id' => $entity->id,
                    'sacrifice_type_id' => $typeId,
                    'quantity' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // 4. إدراج المخازن دفعة واحدة (Bulk Insert) لتحسين الأداء
            if (!empty($stocks)) {
                EntityStock::insert($stocks);
            }

            return $entity;
        });
    }

    /**
     * تحديث بيانات جهة التوزيع
     */
    public function updateEntity(DistributionEntity $entity, array $data): DistributionEntity
    {
        $entity->update($data);

        return $entity;
    }

    /**
     * حذف جهة التوزيع (مع تطبيق قاعدة حماية المخزون)
     */
    public function deleteEntity(DistributionEntity $entity): bool
    {
        return DB::transaction(function () use ($entity) {
            // 1. التحقق من قاعدة العمل: هل يوجد رصيد فعلي (أكبر من صفر) لأي نوع؟
            $hasStock = $entity->entityStocks()->where('quantity', '>', 0)->exists();

            if ($hasStock) {
                throw new Exception("لا يمكن حذف جهة التوزيع لوجود رصيد أضاحي في عهدتها. يجب تصفية العهدة أولاً.");
            }

            // 2. الحذف الآمن (Soft Delete) للمخازن الصفرية أولاً لضمان نظافة قواعد البيانات
            $entity->entityStocks()->delete();

            // 3. حذف جهة التوزيع (Soft Delete)
            return $entity->delete();
        });
    }
}
