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

            // 2. تحديث حركة المخزون
            $this->inventoryService->addStock(
                $supply->sacrifice_type_id,
                $supply->quantity,
                $supply,
                null,                // توزيعة الجهة (null لأنه توريد للمخزن)
                $supply->warehouse_id // تسجيل المخزن المستلم
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
     * تحديث بيانات التوريد (بما في ذلك الكمية) باستخدام استراتيجية المسح والإضافة
     */
    public function update(UpdateSupplyRequest $request, Supply $supply): SupplyResource
    {
        $this->authorize('update', $supply);

        $supply = DB::transaction(function () use ($request, $supply) {
            $validatedData = $request->validated();

            // جلب الكمية الجديدة (إذا لم ترسل في الطلب، نستخدم القديمة)
            $newQuantity = $validatedData['quantity'] ?? $supply->quantity;
            $newSacrificeTypeId = $validatedData['sacrifice_type_id'] ?? $supply->sacrifice_type_id;
            $newWarehouseId = $validatedData['warehouse_id'] ?? $supply->warehouse_id;

            // التحقق مما إذا كان التعديل يمس الحقول المؤثرة على المخزون
            $inventoryChanged = (
                $newQuantity != $supply->quantity ||
                $newSacrificeTypeId != $supply->sacrifice_type_id ||
                $newWarehouseId != $supply->warehouse_id
            );

            if ($inventoryChanged) {
                // 1. تصفير الحركات القديمة (Soft Delete) عبر المايسترو
                $this->inventoryService->reverseDocumentMovements($supply);

                // 2. تحديث بيانات المستند الأصلي (الفاتورة)
                $supply->update($validatedData);

                // 3. إنزال الحركة الجديدة بالبيانات المحدثة
                $this->inventoryService->addStock(
                    $supply->sacrifice_type_id,
                    $supply->quantity,
                    $supply,
                    null,
                    $supply->warehouse_id
                );
            } else {
                // تحديث البيانات العادية فقط (مثل الملاحظات أو الأسعار) دون المساس بحركات المخزون
                $supply->update($validatedData);
            }

            return $supply;
        });

        return new SupplyResource($supply->load(['sacrificeType', 'warehouse']));
    }

    /**
     * حذف التوريد كلياً (مع مسح أثره المخزني)
     */
    public function destroy(Supply $supply): Response
    {
        $this->authorize('delete', $supply);

        DB::transaction(function () use ($supply) {
            // 1. مسح الحركات المرتبطة أولاً لإرجاع الرصيد لحالته السابقة
            $this->inventoryService->reverseDocumentMovements($supply);

            // 2. حذف مستند التوريد نفسه (Soft Delete)
            $supply->delete();
        });

        return response()->noContent();
    }
}
