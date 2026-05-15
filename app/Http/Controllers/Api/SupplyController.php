<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supply;
use App\Http\Requests\Supply\StoreSupplyRequest;
use App\Http\Requests\Supply\UpdateSupplyRequest;
use App\Http\Resources\Api\SupplyResource;
use App\Services\InventoryService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SupplyController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * عرض قائمة عمليات التوريد مع المخازن والأنواع
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Supply::class);

        // إضافة 'warehouse' للتحميل المسبق لضمان ظهور البيانات في الواجهة
        $supplies = Supply::with(['sacrificeType', 'warehouse'])->latest()->get();

        return SupplyResource::collection($supplies);
    }

    /**
     * تسجيل عملية توريد جديدة
     */
    public function store(StoreSupplyRequest $request): SupplyResource
    {
        $this->authorize('create', Supply::class);

        $supply = DB::transaction(function () use ($request) {
            // 1. إنشاء سجل التوريد (يجب أن يحتوي الـ Request على warehouse_id)
            $supply = Supply::create($request->validated());

            // 2. تحديث حركة المخزون - تمرير البارامترات حسب الترتيب الجديد
            $this->inventoryService->addStock(
                $supply->sacrifice_type_id,
                $supply->quantity,
                $supply,
                null,                // البارامتر 4: distribution_entity_id (null لأنه توريد للمخزن)
                $supply->warehouse_id // البارامتر 5: المفقود الذي تسبب في المشكلة
            );

            return $supply;
        });

        return new SupplyResource($supply->load(['sacrificeType', 'warehouse']));
    }

    /**
     * عرض تفاصيل عملية توريد
     */
    public function show(Supply $supply): SupplyResource
    {
        $this->authorize('view', $supply);

        return new SupplyResource($supply->load(['sacrificeType', 'warehouse']));
    }

    /**
     * تحديث بيانات التوريد (الملاحظات والأسعار فقط)
     */
    public function update(UpdateSupplyRequest $request, Supply $supply): SupplyResource
    {
        $this->authorize('update', $supply);

        // الالتزام بقاعدتك: حماية الرصيد المخزني بمنع تعديل الكمية بعد الاعتماد
        $supply->update($request->safe()->only(['supplier_name', 'weight_note', 'total_value', 'notes']));

        return new SupplyResource($supply->load(['sacrificeType', 'warehouse']));
    }

    /**
     * منع حذف التوريدات
     */
    public function destroy(Supply $supply): Response
    {
        $this->authorize('delete', $supply);

        abort(403, 'لا يمكن حذف توريد تم اعتماده ودخل المخزن. يرجى عمل حركة "تسوية صادر" بدلاً من ذلك.');
    }
}
