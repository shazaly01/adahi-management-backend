<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DistributionEntity;
use App\Services\DistributionEntityService;
use App\Http\Resources\Api\DistributionEntityResource;
use App\Http\Requests\DistributionEntity\StoreDistributionEntityRequest;
use App\Http\Requests\DistributionEntity\UpdateDistributionEntityRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Exception;

class DistributionEntityController extends Controller
{
    protected DistributionEntityService $distributionEntityService;

    public function __construct(DistributionEntityService $distributionEntityService)
    {
        $this->distributionEntityService = $distributionEntityService;
    }

    /**
     * عرض قائمة جميع جهات التوزيع.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', DistributionEntity::class);

        $entities = DistributionEntity::latest()->get();

        return DistributionEntityResource::collection($entities);
    }

    /*
     * إنشاء جهة توزيع جديدة.
     */
    public function store(StoreDistributionEntityRequest $request): JsonResponse
    {
        $this->authorize('create', DistributionEntity::class);

        $entity = $this->distributionEntityService->createEntity($request->validated());

        return response()->json([
            'message' => 'تم إنشاء جهة التوزيع والمخازن التابعة لها بنجاح.',
            'data' => new DistributionEntityResource($entity)
        ], 201);
    }

    /**
     * عرض تفاصيل جهة توزيع محددة.
     */
    public function show(DistributionEntity $distributionEntity): DistributionEntityResource
    {
        $this->authorize('view', $distributionEntity);

        return new DistributionEntityResource($distributionEntity);
    }

    /**
     * تحديث بيانات جهة التوزيع.
     */
    public function update(UpdateDistributionEntityRequest $request, DistributionEntity $distributionEntity): JsonResponse
    {
        $this->authorize('update', $distributionEntity);

        $entity = $this->distributionEntityService->updateEntity($distributionEntity, $request->validated());

        return response()->json([
            'message' => 'تم تحديث بيانات جهة التوزيع بنجاح.',
            'data' => new DistributionEntityResource($entity)
        ], 200);
    }

    /**
     * حذف جهة التوزيع.
     */
    public function destroy(DistributionEntity $distributionEntity): JsonResponse
    {
        $this->authorize('delete', $distributionEntity);

        try {
            $this->distributionEntityService->deleteEntity($distributionEntity);

            return response()->json([
                'message' => 'تم حذف جهة التوزيع بنجاح.'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'فشل في عملية الحذف.',
                'error' => $e->getMessage()
            ], 400); // 400 Bad Request بسبب كسر قاعدة العمل (Business Rule)
        }
    }
}
