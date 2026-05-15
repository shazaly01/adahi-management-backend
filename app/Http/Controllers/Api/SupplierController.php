<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Resources\Api\SupplierResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Supplier::class);

        $suppliers = Supplier::latest()->get();

        return SupplierResource::collection($suppliers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupplierRequest $request): SupplierResource
    {
        $this->authorize('create', Supplier::class);

        $supplier = Supplier::create($request->validated());

        return new SupplierResource($supplier);
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier): SupplierResource
    {
        $this->authorize('view', $supplier);

        return new SupplierResource($supplier);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreSupplierRequest $request, Supplier $supplier): SupplierResource
    {
        $this->authorize('update', $supplier);

        $supplier->update($request->validated());

        return new SupplierResource($supplier);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier): Response
    {
        $this->authorize('delete', $supplier);

        $supplier->delete();

        return response()->noContent();
    }
}
