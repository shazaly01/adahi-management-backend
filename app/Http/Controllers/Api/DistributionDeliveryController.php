<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Distribution;
use App\Services\DistributionService;
use App\Http\Requests\Distribution\SearchDeliveryRequest;
use App\Http\Requests\Distribution\ConfirmDeliveryRequest;
use App\Http\Resources\Api\DeliveryDistributionResource;
use Illuminate\Http\JsonResponse;

class DistributionDeliveryController extends Controller
{
    protected DistributionService $distributionService;

    public function __construct(DistributionService $distributionService)
    {
        $this->distributionService = $distributionService;
    }

    /**
     * مسار البحث المخصص لشاشة الموزع
     */
    public function search(SearchDeliveryRequest $request): JsonResponse
    {
        $user = $request->user();

        // حماية إضافية: التأكد من أن المستخدم يتبع لجهة توزيع (رغم وجود الصلاحية)
        if (!$user->distribution_entity_id) {
            return response()->json([
                'message' => 'هذا الحساب غير مرتبط بجهة توزيع محددة، لا يمكن إجراء البحث.'
            ], 403);
        }

        $distributions = $this->distributionService->searchDeliveries(
            $request->validated('search_term'),
            $user->distribution_entity_id
        );

        return response()->json([
            'data' => DeliveryDistributionResource::collection($distributions)
        ]);
    }

    /**
     * مسار تغيير حالة تسليم الأضحية
     */
    public function toggleStatus(ConfirmDeliveryRequest $request, Distribution $distribution): JsonResponse
    {
        $user = $request->user();

        // حماية أمنية صارمة: منع الموزع من تعديل حالة إيصال يتبع لجهة توزيع أخرى
       // التعديل الآمن: تحويل القيمتين إلى Integer قبل المقارنة
if ((int) $distribution->distribution_entity_id !== (int) $user->distribution_entity_id) {
            return response()->json([
                'message' => 'غير مصرح لك بتعديل أو تسليم هذا الإيصال كونه يتبع لجهة أخرى.'
            ], 403);
        }

        $updatedDistribution = $this->distributionService->toggleDeliveryStatus(
            $distribution,
            $request->validated('is_delivered')
        );

        return response()->json([
            'message' => 'تم تحديث حالة تسليم الأضحية بنجاح.',
            'data' => new DeliveryDistributionResource($updatedDistribution)
        ]);
    }
}
