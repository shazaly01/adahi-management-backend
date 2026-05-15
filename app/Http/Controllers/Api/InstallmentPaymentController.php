<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstallmentPayment;
use App\Http\Requests\InstallmentPayment\StoreInstallmentPaymentRequest;
use App\Http\Resources\Api\InstallmentPaymentResource;
use App\Services\InstallmentService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InstallmentPaymentController extends Controller
{
    protected InstallmentService $installmentService;

    public function __construct(InstallmentService $installmentService)
    {
        $this->installmentService = $installmentService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', InstallmentPayment::class);

        $payments = InstallmentPayment::with(['collector', 'contract'])->latest()->get();

        return InstallmentPaymentResource::collection($payments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInstallmentPaymentRequest $request): InstallmentPaymentResource
    {
        $this->authorize('create', InstallmentPayment::class);

        // توجيه الطلب للـ Service لمعالجة قفل السجل (Pessimistic Locking) وتحديث العقد وجدول الأقساط
        $payment = $this->installmentService->collectPayment(
            $request->validated(),
            $request->user()->id
        );

        // تحميل العقد وجدول الأقساط لرؤية أثر الخصم (Row-Splitting) في الواجهة مباشرة
        return new InstallmentPaymentResource($payment->load(['collector', 'contract.schedules']));
    }

    /**
     * Display the specified resource.
     */
    public function show(InstallmentPayment $installmentPayment): InstallmentPaymentResource
    {
        $this->authorize('view', $installmentPayment);

        return new InstallmentPaymentResource($installmentPayment->load(['collector', 'contract']));
    }

    // ملاحظة: تم تجاهل دالتي update و destroy عمداً للحفاظ على النزاهة المحاسبية للإيصالات
}
