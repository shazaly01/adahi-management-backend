<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Http\Resources\Api\InventoryMovementResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryMovementController extends Controller
{
    /**
     * عرض قائمة حركات المخزون مع الفلترة والترقيم
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', InventoryMovement::class);

        // بناء الاستعلام مع تحميل العلاقات الجديدة لضمان أداء عالي (No N+1)
        $query = InventoryMovement::with([
            'sacrificeType',
            'warehouse',
            'distributionEntity',
            'user' // بدلاً من custodian القديم
        ]);

        // تطبيق فلتر نوع الحركة (وارد / صادر) كما تطلبه الواجهة
        if ($request->has('type') && in_array($request->type, ['in', 'out'])) {
            $query->where('movement_type', $request->type);
        }

        // تطبيق البحث البسيط بالمعرف أو نوع المرجع إن وجد
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference_id', 'like', "%{$search}%")
                  ->orWhere('reference_type', 'like', "%{$search}%");
            });
        }

        // الترقيم (Pagination) لضمان سرعة الاستجابة مع البيانات الضخمة
        $movements = $query->latest()->paginate(15);

        return InventoryMovementResource::collection($movements);
    }

    /**
     * عرض تفاصيل حركة مخزنية محددة
     */
    public function show(InventoryMovement $inventoryMovement): InventoryMovementResource
    {
        $this->authorize('view', $inventoryMovement);

        return new InventoryMovementResource(
            $inventoryMovement->load(['sacrificeType', 'warehouse', 'distributionEntity', 'user'])
        );
    }
}
