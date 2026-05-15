<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\EntityStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Exception;

class InventoryService
{
    /**
     * تسجيل حركة إضافة (دخول IN)
     * يمكن أن تكون للمخزن الرئيسي أو لجهة توزيع
     */
    public function addStock(string $sacrificeTypeId, int $quantity, Model $reference, ?string $distributionEntityId = null, ?string $warehouseId = null, ?string $userId = null): InventoryMovement
    {
        return DB::transaction(function () use ($sacrificeTypeId, $quantity, $reference, $distributionEntityId, $warehouseId, $userId) {

            $movement = InventoryMovement::create([
                'sacrifice_type_id'      => $sacrificeTypeId,
                'warehouse_id'           => $warehouseId,           // تسجيل المخزن المستلم
                'distribution_entity_id' => $distributionEntityId,  // أو الجهة المستلمة
                'user_id'                => $userId,
                'movement_type'          => 'in',
                'quantity'               => $quantity,
                'reference_type'         => get_class($reference),
                'reference_id'           => $reference->id,
            ]);

            // تحديث الرصيد السريع لجهة التوزيع (إذا كانت الحركة تابعة لجهة)
            if ($distributionEntityId) {
                $stock = EntityStock::lockForUpdate()->firstOrCreate(
                    ['distribution_entity_id' => $distributionEntityId, 'sacrifice_type_id' => $sacrificeTypeId],
                    ['quantity' => 0]
                );
                $stock->increment('quantity', $quantity);
            }

            return $movement;
        });
    }

    /**
     * تسجيل حركة سحب (خروج OUT)
     */
    public function removeStock(string $sacrificeTypeId, int $quantity, Model $reference, ?string $distributionEntityId = null, ?string $warehouseId = null, ?string $userId = null): InventoryMovement
    {
        return DB::transaction(function () use ($sacrificeTypeId, $quantity, $reference, $distributionEntityId, $warehouseId, $userId) {

            // 1. التحقق من توفر الرصيد بناءً على المصدر (جهة أم مخزن)
            if ($distributionEntityId) {
                $stock = EntityStock::where('distribution_entity_id', $distributionEntityId)
                    ->where('sacrifice_type_id', $sacrificeTypeId)
                    ->lockForUpdate()
                    ->first();

                if (!$stock || $stock->quantity < $quantity) {
                    throw new Exception("الرصيد الحالي للجهة الموزعة لا يكفي.");
                }
                $stock->decrement('quantity', $quantity);
            } else {
                // التحقق من رصيد مخزن معين بدلاً من الرصيد العام المبهم
                $currentBalance = $this->getWarehouseBalance($sacrificeTypeId, $warehouseId);
                if ($currentBalance < $quantity) {
                    throw new Exception("الرصيد في المخزن المحدد لا يكفي لإتمام هذه العملية.");
                }
            }

            // 2. تسجيل الحركة
            return InventoryMovement::create([
                'sacrifice_type_id'      => $sacrificeTypeId,
                'warehouse_id'           => $warehouseId,
                'distribution_entity_id' => $distributionEntityId,
                'user_id'                => $userId,
                'movement_type'          => 'out',
                'quantity'               => $quantity,
                'reference_type'         => get_class($reference),
                'reference_id'           => $reference->id,
            ]);
        });
    }

    /**
     * نقل عهدة (من مخزن محدد إلى جهة توزيع محددة)
     */
    public function transferToEntity(string $sacrificeTypeId, int $quantity, string $distributionEntityId, string $warehouseId, Model $reference, ?string $userId = null): void
    {
        DB::transaction(function () use ($sacrificeTypeId, $quantity, $distributionEntityId, $warehouseId, $reference, $userId) {
            // 1. خروج من المخزن المحدد
            $this->removeStock($sacrificeTypeId, $quantity, $reference, null, $warehouseId, $userId);

            // 2. دخول في عهدة الجهة
            $this->addStock($sacrificeTypeId, $quantity, $reference, $distributionEntityId, null, $userId);
        });
    }

    /**
     * حساب رصيد مخزن محدد بدقة
     */
    public function getWarehouseBalance(string $sacrificeTypeId, ?string $warehouseId = null): int
    {
        $query = InventoryMovement::where('sacrifice_type_id', $sacrificeTypeId)
            ->whereNull('distribution_entity_id');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $in = (clone $query)->where('movement_type', 'in')->sum('quantity');
        $out = (clone $query)->where('movement_type', 'out')->sum('quantity');

        return $in - $out;
    }

    /**
     * تعديل حركة سابقة (الحفاظ على منطق الـ Soft Delete والتحسيب الذي بنيناه)
     */
    public function updateMovementQuantity(string $movementId, int $newQuantity, ?string $userId = null): InventoryMovement
    {
        return DB::transaction(function () use ($movementId, $newQuantity, $userId) {
            $oldMovement = InventoryMovement::lockForUpdate()->findOrFail($movementId);

            if ($oldMovement->quantity === $newQuantity) {
                return $oldMovement;
            }

            // منع تعديل الحركات المرتبطة بجهات التوزيع إذا كان الرصيد لا يسمح
            if ($oldMovement->distribution_entity_id) {
                $stock = EntityStock::where('distribution_entity_id', $oldMovement->distribution_entity_id)
                    ->where('sacrifice_type_id', $oldMovement->sacrifice_type_id)
                    ->lockForUpdate()
                    ->first();

                $diff = $newQuantity - $oldMovement->quantity;
                $newStock = ($oldMovement->movement_type === 'in') ? $stock->quantity + $diff : $stock->quantity - $diff;

                if ($newStock < 0) {
                    throw new Exception("التعديل سيؤدي لرصيد سالب لدى الجهة.");
                }
                $stock->update(['quantity' => $newStock]);
            }

            $oldMovement->delete();

            return InventoryMovement::create([
                'sacrifice_type_id'      => $oldMovement->sacrifice_type_id,
                'warehouse_id'           => $oldMovement->warehouse_id,
                'distribution_entity_id' => $oldMovement->distribution_entity_id,
                'user_id'                => $userId ?? $oldMovement->user_id,
                'movement_type'          => $oldMovement->movement_type,
                'quantity'               => $newQuantity,
                'reference_type'         => $oldMovement->reference_type,
                'reference_id'           => $oldMovement->reference_id,
            ]);
        });
    }
}
