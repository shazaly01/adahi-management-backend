<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Http\Requests\Beneficiary\StoreBeneficiaryRequest;
use App\Http\Requests\Beneficiary\UpdateBeneficiaryRequest;
use App\Http\Resources\Api\BeneficiaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class BeneficiaryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Beneficiary::class);

        $user = auth()->user();
        $query = Beneficiary::with('user')->latest();

        // تطبيق العزل المناطقي: إذا كان المستخدم موزعاً، يرى مستفيديه فقط
        if ($user->hasRole('Distributor')) {
            $query->where('user_id', $user->id);
        }

        $beneficiaries = $query->get();

        return BeneficiaryResource::collection($beneficiaries);
    }

    /**
     * Check if a beneficiary exists by National ID, Phone, or Name.
     */
    public function check(Request $request)
    {
        // الواجهة الأمامية ترسل المتغير باسم national_id لكنه قد يحتوي على هاتف أو اسم
        $term = $request->query('national_id');

        if (!$term) {
            return response()->json(['message' => 'يرجى إدخال معامل البحث.'], 400);
        }

        $user = $request->user();

        $query = Beneficiary::with('user')->withCount('distributions')->where(function ($q) use ($term) {
            $q->where('national_id', $term)
              ->orWhere('phone', $term)
              ->orWhere('name', 'LIKE', "%{$term}%");
        });

        // تطبيق العزل المناطقي عند البحث أيضاً
        if ($user->hasRole('Distributor')) {
            $query->where('user_id', $user->id);
        }

        $beneficiary = $query->first();

        if (!$beneficiary) {
            return response()->json(['message' => 'المستفيد غير مسجل مسبقاً.'], 404);
        }

        return new BeneficiaryResource($beneficiary);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBeneficiaryRequest $request): BeneficiaryResource
    {
        $this->authorize('create', Beneficiary::class);

        $data = $request->validated();

        // حقن معرّف الجهة الموزعة برمجياً وبشكل آمن تماماً
        $data['user_id'] = $request->user()->id;

        $beneficiary = Beneficiary::create($data);

        return new BeneficiaryResource($beneficiary->load('user'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Beneficiary $beneficiary): BeneficiaryResource
    {
        $this->authorize('view', $beneficiary);

        return new BeneficiaryResource($beneficiary->load('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBeneficiaryRequest $request, Beneficiary $beneficiary): BeneficiaryResource
    {
        $this->authorize('update', $beneficiary);

        $beneficiary->update($request->validated());

        return new BeneficiaryResource($beneficiary->load('user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Beneficiary $beneficiary): Response
    {
        $this->authorize('delete', $beneficiary);

        $beneficiary->delete();

        return response()->noContent();
    }
}
