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
     * تحديث العهدة (معالجة التعديل على الكميات والجهات بشكل آمن)
     */
    public function updateAllocation(Allocation $allocation, array $data): Allocation
    {
        return DB::transaction(function () use ($allocation, $data) {
            $newQuantity = $data['quantity'] ?? $allocation->quantity;
            $newSacrificeTypeId = $data['sacrifice_type_id'] ?? $allocation->sacrifice_type_id;
            $newWarehouseId = $data['warehouse_id'] ?? $allocation->warehouse_id;
            $newDistributionEntityId = $data['distribution_entity_id'] ?? $allocation->distribution_entity_id;

            $inventoryChanged = (
                $newQuantity != $allocation->quantity ||
                $newSacrificeTypeId != $allocation->sacrifice_type_id ||
                $newWarehouseId != $allocation->warehouse_id ||
                $newDistributionEntityId != $allocation->distribution_entity_id
            );

            if ($inventoryChanged) {
                // 1. تصفير الحركتين القديمتين (الخروج والدخول) وعكس تأثيرهما على الرصيد
                $this->inventoryService->reverseDocumentMovements($allocation);

                // 2. تحديث بيانات المستند
                $allocation->update($data);

                // 3. إنزال الحركتين الجديدتين بالبيانات المحدثة
                $this->inventoryService->transferToEntity(
                    $allocation->sacrifice_type_id,
                    $allocation->quantity,
                    $allocation->distribution_entity_id,
                    $allocation->warehouse_id,
                    $allocation
                );
            } else {
                // تحديث الملاحظات أو القيمة فقط بدون تأثير مخزني
                $allocation->update($data);
            }

            return $allocation;
        });
    }

    /**
     * حذف العهدة كلياً وإرجاع المخزون لحالته السابقة
     */
    public function deleteAllocation(Allocation $allocation): void
    {
        DB::transaction(function () use ($allocation) {
            // 1. عكس التأثير المخزني للحركتين (إرجاع الرصيد للمخزن وخصمه من الجهة)
            $this->inventoryService->reverseDocumentMovements($allocation);

            // 2. حذف مستند العهدة (Soft Delete)
            $allocation->delete();
        });
    }

    /**
     * جلب بيانات إيصال تسليم الجهة وتجهيز العلاقات للطباعة
     */
    public function getReceipt(Allocation $allocation): Allocation
    {
        return $allocation->loadMissing(['distributionEntity', 'sacrificeType', 'warehouse']);
    }
}
