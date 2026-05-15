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
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class DistributionController extends Controller
{
    protected DistributionService $distributionService;

    public function __construct(DistributionService $distributionService)
    {
        $this->distributionService = $distributionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Distribution::class);

        // إضافة installmentContract لجلب بيانات التقسيط إن وجدت
        $distributions = Distribution::with(['beneficiary', 'sacrificeType', 'user', 'installmentContract'])->latest()->get();

        return DistributionResource::collection($distributions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDistributionRequest $request): DistributionResource
    {
        $this->authorize('create', Distribution::class);

        // توجيه الطلب للـ Service لمعالجة العملية المعقدة (مخزون + أقساط + مرفقات)
        $distribution = $this->distributionService->distribute(
            $request->validated(),
            $request->user()->id
        );

        return new DistributionResource($distribution->load(['beneficiary', 'sacrificeType', 'user', 'installmentContract']));
    }

    /**
     * Display the specified resource.
     */
    public function show(Distribution $distribution): DistributionResource
    {
        $this->authorize('view', $distribution);

        return new DistributionResource($distribution->load(['beneficiary', 'sacrificeType', 'user', 'installmentContract']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDistributionRequest $request, Distribution $distribution): DistributionResource
    {
        $this->authorize('update', $distribution);

        // يُسمح فقط بتحديث المرفقات. تغيير نوع الأضحية أو السعر يتطلب حركة عكسية (تسوية)
        if ($request->hasFile('beneficiary_image')) {
            // حذف الصورة القديمة
            if ($distribution->beneficiary_image) {
                Storage::disk('public')->delete($distribution->beneficiary_image);
            }

            // الالتزام التام بالاحتفاظ باسم الملف الأصلي
            $imageFile = $request->file('beneficiary_image');
            $safeImageName = time() . '_' . $imageFile->getClientOriginalName();
            $distribution->beneficiary_image = $imageFile->storeAs('distributions/images', $safeImageName, 'public');
        }

        if ($request->hasFile('beneficiary_document')) {
            // حذف المستند القديم
            if ($distribution->beneficiary_document) {
                Storage::disk('public')->delete($distribution->beneficiary_document);
            }

            // الالتزام التام بالاحتفاظ باسم الملف الأصلي
            $docFile = $request->file('beneficiary_document');
            $safeDocName = time() . '_' . $docFile->getClientOriginalName();
            $distribution->beneficiary_document = $docFile->storeAs('distributions/documents', $safeDocName, 'public');
        }

        $distribution->save();

        return new DistributionResource($distribution->load(['beneficiary', 'sacrificeType', 'user', 'installmentContract']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Distribution $distribution): Response
    {
        $this->authorize('delete', $distribution);

        abort(403, 'لا يمكن حذف عملية توزيع تمت بالفعل. يجب عمل تسوية عكسية للمخزون والأقساط.');
    }

    /**
     * استخراج الإيصالات (مفردة أو جماعية)
     */
    public function receipts(Request $request): AnonymousResourceCollection
    {
        // التحقق من أن الواجهة الأمامية أرسلت مصفوفة من الـ IDs
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:distributions,id',
        ]);

        // جلب البيانات عبر الخدمة
        $distributions = $this->distributionService->getReceipts($request->ids);

        // إعادة البيانات باستخدام الـ Resource الحالي الممتاز
        return DistributionResource::collection($distributions);
    }
}
