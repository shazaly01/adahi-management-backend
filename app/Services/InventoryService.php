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
                'warehouse_id'           => $warehouseId,
                'distribution_entity_id' => $distributionEntityId,
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
                // التحقق من رصيد مخزن معين
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
     * الجوهر الجديد: عكس وإلغاء تأثير جميع الحركات المرتبطة بمستند معين (استعداداً لإعادة إنشائها بالكميات الجديدة)
     */
    public function reverseDocumentMovements(Model $reference): void
    {
        // جلب جميع الحركات التابعة لهذا المستند (سواء كانت حركة واحدة كالتوريد، أو حركتين كالتخصيص)
        $movements = InventoryMovement::where('reference_type', get_class($reference))
            ->where('reference_id', $reference->id)
            ->lockForUpdate()
            ->get();

        foreach ($movements as $movement) {
            // إذا كانت الحركة مرتبطة بجهة توزيع، يجب عكس تأثيرها على الرصيد التجميعي أولاً
            if ($movement->distribution_entity_id) {
                $stock = EntityStock::where('distribution_entity_id', $movement->distribution_entity_id)
                    ->where('sacrifice_type_id', $movement->sacrifice_type_id)
                    ->lockForUpdate()
                    ->first();

                if ($stock) {
                    if ($movement->movement_type === 'in') {
                        // عكس الدخول هو طرح الكمية
                        $stock->decrement('quantity', $movement->quantity);
                    } elseif ($movement->movement_type === 'out') {
                        // عكس الخروج هو إضافة الكمية
                        $stock->increment('quantity', $movement->quantity);
                    }
                }
            }

            // أخيراً: حذف الحركة (Soft Delete) للاحتفاظ بها في الأرشيف وإخراجها من الحسابات الحالية
            $movement->delete();
        }
    }
}
