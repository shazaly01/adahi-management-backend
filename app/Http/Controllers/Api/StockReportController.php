<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StockReportService;
use Illuminate\Http\JsonResponse;

class StockReportController extends Controller
{
    protected StockReportService $stockReportService;

    public function __construct(StockReportService $stockReportService)
    {
        $this->stockReportService = $stockReportService;
    }

    /**
     * مسار جلب أرصدة المخازن الرئيسية اللحظية
     * يمكن استخدامه في لوحة التحكم (Dashboard)
     */
    public function warehouses(): JsonResponse
    {
        // يمكنك إضافة صلاحية هنا، مثلاً:
        // $this->authorize('viewAny', \App\Models\Warehouse::class);

        $report = $this->stockReportService->getWarehouseStock();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب أرصدة المخازن بنجاح',
            'data'    => $report
        ]);
    }

    /**
     * مسار جلب أرصدة جهات التوزيع (العهد والمنصرف)
     */
    public function entities(): JsonResponse
    {
        // يمكنك إضافة صلاحية هنا، مثلاً:
        // $this->authorize('viewAny', \App\Models\DistributionEntity::class);

        $report = $this->stockReportService->getEntityStock();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب تقرير عهد جهات التوزيع بنجاح',
            'data'    => $report
        ]);
    }
}
