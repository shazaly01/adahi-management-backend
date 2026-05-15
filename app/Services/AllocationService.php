<?php

namespace App\Services;

use App\Models\Allocation;
use Illuminate\Support\Facades\DB;

class AllocationService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * تسجيل عملية تسليم عهدة جديدة (نقل من مخزن إلى جهة)
     */
    public function allocate(array $data): Allocation
    {
        return DB::transaction(function () use ($data) {
            // إنشاء سجل العهدة
            $allocation = Allocation::create($data);

            // تنفيذ حركة المخزون المزدوجة (خصم من المخزن الرئيسي وإضافة لجهة التوزيع)
            $this->inventoryService->transferToEntity(
                $allocation->sacrifice_type_id,
                $allocation->quantity,
                $allocation->distribution_entity_id,
                $allocation->warehouse_id,
                $allocation // Reference للحركة
            );

            return $allocation;
        });
    }

    /**
     * جلب بيانات إيصال تسليم الجهة وتجهيز العلاقات للطباعة
     */
    public function getReceipt(Allocation $allocation): Allocation
    {
        // استخدام loadMissing لضمان تحميل العلاقات اللازمة لطباعة الإيصال
        // (الجهة المستلمة، المخزن المصدر، ونوع الأضحية) دون تكرار الاستعلام
        return $allocation->loadMissing(['distributionEntity', 'sacrificeType', 'warehouse']);
    }
}
