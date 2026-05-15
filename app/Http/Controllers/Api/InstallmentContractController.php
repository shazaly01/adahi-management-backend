<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstallmentContract;
use App\Http\Resources\Api\InstallmentContractResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InstallmentContractController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', InstallmentContract::class);

        // تم إضافة 'schedules' لجلب جدول الأقساط الشهري مع العقد
        $contracts = InstallmentContract::with(['beneficiary', 'payments.collector', 'schedules'])->latest()->get();

        return InstallmentContractResource::collection($contracts);
    }

    /**
     * Display the specified resource.
     */
    public function show(InstallmentContract $installmentContract): InstallmentContractResource
    {
        $this->authorize('view', $installmentContract);

        return new InstallmentContractResource($installmentContract->load(['beneficiary', 'payments.collector', 'schedules']));
    }
}
