<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Distribution;
use App\Http\Requests\Distribution\StoreDistributionRequest;
use App\Http\Requests\Distribution\UpdateDistributionRequest;
use App\Http\Resources\Api\DistributionResource;
use App\Services\DistributionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class DistributionController extends Controller
{
    protected DistributionService $distributionService;

    public function __construct(DistributionService $distributionService)
    {
        $this->distributionService = $distributionService;
    }

    /**
     * عرض قائمة عمليات التوزيع
     */
  /**
     * عرض قائمة عمليات التوزيع مع دعم البحث
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Distribution::class);

        $search = $request->query('search');

        $query = Distribution::with(['beneficiary', 'sacrificeType', 'user', 'installmentContract'])
            ->latest();

        // تطبيق الفلترة إذا تم تمرير كلمة بحث
        if (!empty($search)) {
            $query->whereHas('beneficiary', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('national_id', 'like', "%{$search}%");
            });
        }

        $distributions = $query->get();

        return DistributionResource::collection($distributions);
    }

    /**
     * تسجيل عملية توزيع جديدة (مخزون + أقساط)
     */
    public function store(StoreDistributionRequest $request): DistributionResource
    {
        $this->authorize('create', Distribution::class);

        // توجيه الطلب للـ Service لمعالجة العملية المعقدة
        $distribution = $this->distributionService->distribute(
            $request->validated(),
            $request->user()->id
        );

        return new DistributionResource($distribution->load(['beneficiary', 'sacrificeType', 'user', 'installmentContract']));
    }

    /**
     * عرض تفاصيل عملية توزيع محددة
     */
    public function show(Distribution $distribution): DistributionResource
    {
        $this->authorize('view', $distribution);

        return new DistributionResource($distribution->load(['beneficiary', 'sacrificeType', 'user', 'installmentContract']));
    }

    /**
     * تحديث بيانات التوزيع (الكمية، السعر، الأقساط، أو المرفقات)
     */
    public function update(UpdateDistributionRequest $request, Distribution $distribution): DistributionResource
    {
        $this->authorize('update', $distribution);

        // 1. تحديث المرفقات أولاً (إن وُجدت) عبر الـ Service
        $this->distributionService->updateAttachments(
            $distribution,
            $request->file('beneficiary_image'),
            $request->file('beneficiary_document')
        );

        // 2. تحديث البيانات الجوهرية (والمزامنة المخزنية/المحاسبية) عبر الـ Service
        $distribution = $this->distributionService->updateDistribution(
            $distribution,
            $request->validated(),
            $request->user()->id
        );

        return new DistributionResource($distribution->load(['beneficiary', 'sacrificeType', 'user', 'installmentContract']));
    }

    /**
     * حذف عملية التوزيع كلياً
     */
    public function destroy(Distribution $distribution): Response
    {
        $this->authorize('delete', $distribution);

        // الـ Service ستتكفل بمسح العقد (إذا لم يُسدد) وعكس الحركات المخزنية قبل مسح السجل
        $this->distributionService->deleteDistribution($distribution);

        return response()->noContent();
    }

    /**
     * استخراج الإيصالات (مفردة أو جماعية)
     */
    public function receipts(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:distributions,id',
        ]);

        $distributions = $this->distributionService->getReceipts($request->ids);

        return DistributionResource::collection($distributions);
    }


    /**
     * استخراج الإيصالات للعامة (بدون مصادقة أو صلاحيات) عبر روابط الواتساب
     */
    public function publicReceipts(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:distributions,id',
        ]);

        // جلب البيانات مباشرة من الـ Service دون فحص صلاحيات المستخدم لفتح الجلسة للمستفيدين
        $distributions = $this->distributionService->getReceipts($request->ids);

        return DistributionResource::collection($distributions);
    }
}
