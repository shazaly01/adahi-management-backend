<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Distribution;
use App\Models\EntityStock;
use App\Models\Beneficiary;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{



/**
     * 1. لوحة القيادة المركزية (Dashboard) - غنية بالإحصائيات والتحليلات
     */
    public function dashboard(Request $request): JsonResponse
    {
        // التحقق من الصلاحية (يمكنك تغييرها لـ dashboard.view إذا أردت)
       // abort_if(!$request->user()->can('dashboard.view'), 403, 'لا تملك صلاحية عرض لوحة التحكم');

        // --- 1. المؤشرات الرئيسية (KPIs) ---
        $totalBeneficiaries = Beneficiary::count();
        $totalDistributed = Distribution::sum('quantity');

        $totalIn = InventoryMovement::where('movement_type', 'in')->sum('quantity');
        $totalOut = InventoryMovement::where('movement_type', 'out')->sum('quantity');
        $currentGlobalStock = $totalIn - $totalOut;

        // --- 2. إحصائيات التوزيع حسب نوع الأضحية (ممتاز للرسوم البيانية) ---
        $distributionsByType = Distribution::select('sacrifice_type_id', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('sacrifice_type_id')
            ->with('sacrificeType:id,name') // جلب الاسم فقط لتخفيف الحمولة
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->sacrificeType ? $item->sacrificeType->name : 'غير محدد',
                    'total' => (int) $item->total_quantity,
                ];
            });

        // --- 3. أفضل الجهات توزيعاً (Top 5 Distribution Entities) ---
        $topEntities = Distribution::select('distribution_entity_id', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('distribution_entity_id')
            ->with('distributionEntity:id,name')
            ->orderByDesc('total_quantity')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->distributionEntity ? $item->distributionEntity->name : 'جهة غير محددة',
                    'total' => (int) $item->total_quantity,
                ];
            });

        // --- 4. أحدث النشاطات (آخر 5 عمليات توزيع) ---
        $recentDistributions = Distribution::with([
                'beneficiary:id,name',
                'distributionEntity:id,name',
                'sacrificeType:id,name'
            ])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'receipt_number' => $item->receipt_number,
                    'beneficiary_name' => $item->beneficiary ? $item->beneficiary->name : 'غير معروف',
                    'entity_name' => $item->distributionEntity ? $item->distributionEntity->name : 'غير محدد',
                    'sacrifice_type' => $item->sacrificeType ? $item->sacrificeType->name : '',
                    'quantity' => (int) $item->quantity,
                    'date' => $item->created_at->format('Y-m-d H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'kpis' => [
                    'total_beneficiaries' => $totalBeneficiaries,
                    'total_distributed' => (int) $totalDistributed,
                    'current_global_stock' => (int) $currentGlobalStock,
                ],
                'charts' => [
                    'distributions_by_type' => $distributionsByType,
                    'top_entities' => $topEntities,
                ],
                'recent_activities' => $recentDistributions,
            ]
        ]);
    }
    /**
     * 1. تقرير حركة وأرصدة المخزون (الدفتر العام)
     */
    public function inventoryReport(Request $request): JsonResponse
    {
        // التحقق من الصلاحية
        //abort_if(!$request->user()->hasPermissionTo('report.view'), 403, 'لا تملك صلاحية عرض التقارير');

        $query = InventoryMovement::with(['warehouse', 'distributionEntity', 'sacrificeType', 'user']);

        // تطبيق الفلاتر الديناميكية
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('distribution_entity_id')) {
            $query->where('distribution_entity_id', $request->distribution_entity_id);
        }
        if ($request->filled('sacrifice_type_id')) {
            $query->where('sacrifice_type_id', $request->sacrifice_type_id);
        }
        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        $totalIn = (clone $query)->where('movement_type', 'in')->sum('quantity');
        $totalOut = (clone $query)->where('movement_type', 'out')->sum('quantity');
        $netBalance = $totalIn - $totalOut;

        $movements = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_in' => (int) $totalIn,
                    'total_out' => (int) $totalOut,
                    'net_balance' => (int) $netBalance,
                ],
                'movements' => $movements
            ]
        ]);
    }

    /**
     * 2. تقرير التوزيع والمستفيدين (شاشة ديناميكية ذكية)
     */
    public function distributionsReport(Request $request): JsonResponse
    {
        // التحقق من الصلاحية
       // abort_if(!$request->user()->hasPermissionTo('report.view'), 403, 'لا تملك صلاحية عرض التقارير');

        $query = Distribution::with(['beneficiary', 'distributionEntity', 'sacrificeType']);

        // 1. تطبيق الفلاتر
        if ($request->filled('distribution_entity_id')) {
            $query->where('distribution_entity_id', $request->distribution_entity_id);
        }
        if ($request->filled('group')) {
            $query->where('group', $request->group);
        }
        if ($request->filled('delivery_location')) {
            $query->where('delivery_location', $request->delivery_location);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $distributions = $query->latest()->get();

        // 2. حساب الرصيد الحالي (Current Balance) من EntityStock بناءً على الفلاتر
        $stockQuery = EntityStock::query();
        if ($request->filled('distribution_entity_id')) {
            $stockQuery->where('distribution_entity_id', $request->distribution_entity_id);
        }
        $currentBalance = (int) $stockQuery->sum('quantity');

        // 3. تحديد نوع التجميع (Group By)
        $groupBy = $request->get('group_by', 'all');

        if ($groupBy === 'location') {
            $groupedDistributions = $distributions->groupBy(function ($item) {
                return $item->delivery_location ?? 'بدون مكان تسليم';
            });
        } elseif ($groupBy === 'group') {
            $groupedDistributions = $distributions->groupBy(function ($item) {
                return $item->group ?? 'بدون مجموعة';
            });
        } elseif ($groupBy === 'entity') {
            $groupedDistributions = $distributions->groupBy(function ($item) {
                return $item->distributionEntity ? $item->distributionEntity->name : 'جهة غير محددة';
            });
        } else {
            // حالة 'all' (الكل) - يتم وضعهم في مجموعة واحدة اسمها "الكل" لتوحيد هيكل JSON للـ Frontend
            $groupedDistributions = collect(['الكل' => $distributions]);
        }

        // 4. تشكيل البيانات النهائية
        $formattedData = $groupedDistributions->map(function ($items, $groupName) {

            // تجميع المستفيدين داخل هذا القسم
            $beneficiariesList = $items->groupBy('beneficiary_id')->map(function ($benItems) {
                $beneficiary = $benItems->first()->beneficiary;
                return [
                    'beneficiary_id' => $beneficiary ? $beneficiary->id : null,
                    'beneficiary_name' => $beneficiary ? $beneficiary->name : 'مستفيد غير معروف',
                    'national_id' => $beneficiary ? $beneficiary->national_id : '',
                    'total_quantity' => (int) $benItems->sum('quantity'),
                    'details' => $benItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'receipt_number' => $item->receipt_number,
                            'sacrifice_type' => $item->sacrificeType ? $item->sacrificeType->name : 'غير محدد',
                            'quantity' => (int) $item->quantity,
                            'delivery_date' => $item->delivery_date ? $item->delivery_date->format('Y-m-d') : null,
                            'is_delivered' => (bool) $item->is_delivered,
                        ];
                    })->values()
                ];
            })->values();

            return [
                'group_name' => (string) $groupName,
                'total_group_quantity' => (int) $items->sum('quantity'),
                'beneficiaries' => $beneficiariesList
            ];
        })->values();

        // 5. إرجاع الاستجابة
        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_distributed' => (int) $distributions->sum('quantity'),
                    'current_balance' => $currentBalance,
                ],
                'grouped_data' => $formattedData
            ]
        ]);
    }
}
