<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\EntityStock;
use Illuminate\Support\Facades\DB;

class StockReportService
{
    /**
     * تقرير أرصدة المخازن الرئيسية اللحظي (On-the-fly)
     */
    public function getWarehouseStock(): array
    {
        // جلب جميع الحركات المجمعة للمخازن الرئيسية باستخدام استعلام متقدم لسرعة الأداء
        $balances = InventoryMovement::select(
            'warehouse_id',
            'sacrifice_type_id',
            DB::raw("SUM(CASE WHEN movement_type = 'in' THEN quantity ELSE 0 END) as total_in"),
            DB::raw("SUM(CASE WHEN movement_type = 'out' THEN quantity ELSE 0 END) as total_out")
        )
        ->whereNull('distribution_entity_id') // التأكد من أنها حركات مخزن رئيسي فقط
        ->whereNotNull('warehouse_id')
        ->groupBy('warehouse_id', 'sacrifice_type_id')
        ->with(['warehouse', 'sacrificeType'])
        ->get();

        // إعادة هيكلة البيانات في مصفوفة نظيفة لسهولة العرض في الواجهة (v-for)
        $report = [];

        foreach ($balances as $balance) {
            $warehouseId = $balance->warehouse_id;
            $warehouseName = $balance->warehouse->name ?? 'مخزن غير محدد';
            $sacrificeName = $balance->sacrificeType->name ?? 'غير محدد';

            $currentBalance = $balance->total_in - $balance->total_out;

            if (!isset($report[$warehouseId])) {
                $report[$warehouseId] = [
                    'warehouse_id'   => $warehouseId,
                    'warehouse_name' => $warehouseName,
                    'stocks'         => []
                ];
            }

            $report[$warehouseId]['stocks'][] = [
                'sacrifice_type_id'   => $balance->sacrifice_type_id,
                'sacrifice_type_name' => $sacrificeName,
                'total_in'            => (int) $balance->total_in,
                'total_out'           => (int) $balance->total_out,
                'current_balance'     => (int) $currentBalance,
            ];
        }

        return array_values($report);
    }

    /**
     * تقرير أرصدة جهات التوزيع (العُهد الحالية وما تم توزيعه)
     */
    public function getEntityStock(): array
    {
        // 1. جلب العهد الحالية السريعة من جدول EntityStock (الرصيد المتاح)
        $currentStocks = EntityStock::with(['distributionEntity', 'sacrificeType'])->get();

        // 2. حساب إجمالي المنصرف (الموزع) لكل جهة ولكل نوع أضحية من سجل الحركات
        $distributed = InventoryMovement::select(
            'distribution_entity_id',
            'sacrifice_type_id',
            DB::raw("SUM(quantity) as total_distributed")
        )
        ->whereNotNull('distribution_entity_id')
        ->where('movement_type', 'out') // حساب حركات التوزيع النهائي فقط
        ->groupBy('distribution_entity_id', 'sacrifice_type_id')
        ->get()
        ->keyBy(function($item) {
            // إنشاء مفتاح فريد لسهولة الدمج
            return $item->distribution_entity_id . '_' . $item->sacrifice_type_id;
        });

        // 3. دمج البيانات وإعادة هيكلتها للواجهة الأمامية
        $report = [];

        foreach ($currentStocks as $stock) {
            $entityId = $stock->distribution_entity_id;
            $entityName = $stock->distributionEntity->name ?? 'جهة غير محددة';
            $sacrificeTypeId = $stock->sacrifice_type_id;
            $sacrificeName = $stock->sacrificeType->name ?? 'غير محدد';

            $key = $entityId . '_' . $sacrificeTypeId;
            $totalDistributed = isset($distributed[$key]) ? (int) $distributed[$key]->total_distributed : 0;

            if (!isset($report[$entityId])) {
                $report[$entityId] = [
                    'distribution_entity_id'   => $entityId,
                    'distribution_entity_name' => $entityName,
                    'stocks'                   => []
                ];
            }

            $report[$entityId]['stocks'][] = [
                'sacrifice_type_id'   => $sacrificeTypeId,
                'sacrifice_type_name' => $sacrificeName,
                'current_custody'     => (int) $stock->quantity, // العهدة الحالية المتاحة للصرف الآن
                'total_distributed'   => $totalDistributed,      // إجمالي ما تم صرفه للمستفيدين
                'total_received'      => (int) $stock->quantity + $totalDistributed // إجمالي ما استلمته الجهة من المخزن من البداية
            ];
        }

        return array_values($report);
    }
}
