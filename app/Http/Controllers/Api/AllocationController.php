<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Allocation;
use App\Http\Requests\Allocation\StoreAllocationRequest;
use App\Http\Requests\Allocation\UpdateAllocationRequest;
use App\Http\Resources\Api\AllocationResource;
use App\Services\AllocationService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AllocationController extends Controller
{
    protected AllocationService $allocationService;

    public function __construct(AllocationService $allocationService)
    {
        $this->allocationService = $allocationService;
    }

    /**
     * عرض قائمة العُهد المسلمة لجهات التوزيع
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Allocation::class);

        $allocations = Allocation::with(['distributionEntity', 'sacrificeType', 'warehouse'])
            ->latest()
            ->paginate(request('per_page', 15));

        return AllocationResource::collection($allocations);
    }

    /**
     * تسجيل عملية تسليم عهدة جديدة (نقل من مخزن إلى جهة)
     */
    public function store(StoreAllocationRequest $request): AllocationResource
    {
        $this->authorize('create', Allocation::class);

        // توجيه الطلب للـ Service لمعالجة العملية المعقدة (تسجيل + مخزون)
        $allocation = $this->allocationService->allocate($request->validated());

        return new AllocationResource($allocation->load(['distributionEntity', 'sacrificeType', 'warehouse']));
    }

    /**
     * عرض تفاصيل عهدة محددة
     */
    public function show(Allocation $allocation): AllocationResource
    {
        $this->authorize('view', $allocation);

        return new AllocationResource($allocation->load(['distributionEntity', 'sacrificeType', 'warehouse']));
    }

    /**
     * تحديث بيانات العهدة (مع دعم التعديل المخزني عبر الـ Service)
     */
    public function update(UpdateAllocationRequest $request, Allocation $allocation): AllocationResource
    {
        $this->authorize('update', $allocation);

        // إرسال البيانات للـ Service للتحقق من التغيير المخزني ومعالجته
        $allocation = $this->allocationService->updateAllocation($allocation, $request->validated());

        return new AllocationResource($allocation->load(['distributionEntity', 'sacrificeType', 'warehouse']));
    }

    /**
     * حذف العهدة كلياً وعكس حركاتها المخزنية
     */
    public function destroy(Allocation $allocation): Response
    {
        $this->authorize('delete', $allocation);

        // توجيه أمر الحذف للـ Service
        $this->allocationService->deleteAllocation($allocation);

        return response()->noContent();
    }

    /**
     * استخراج إيصال تسليم الجهة
     */
    public function receipt(Allocation $allocation): AllocationResource
    {
        // جلب البيانات مع العلاقات عبر الخدمة
        $receiptData = $this->allocationService->getReceipt($allocation);

        // إرجاع البيانات مهيأة للطباعة في الواجهة الأمامية
        return new AllocationResource($receiptData);
    }
}
