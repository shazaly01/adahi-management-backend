<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Http\Requests\Warehouse\StoreWarehouseRequest;
use App\Http\Resources\Api\WarehouseResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Warehouse::class);

        $warehouses = Warehouse::latest()->get();

        return WarehouseResource::collection($warehouses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWarehouseRequest $request): WarehouseResource
    {
        $this->authorize('create', Warehouse::class);

        $warehouse = Warehouse::create($request->validated());

        return new WarehouseResource($warehouse);
    }

    /**
     * Display the specified resource.
     */
    public function show(Warehouse $warehouse): WarehouseResource
    {
        $this->authorize('view', $warehouse);

        return new WarehouseResource($warehouse);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreWarehouseRequest $request, Warehouse $warehouse): WarehouseResource
    {
        $this->authorize('update', $warehouse);

        $warehouse->update($request->validated());

        return new WarehouseResource($warehouse);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Warehouse $warehouse): Response
    {
        $this->authorize('delete', $warehouse);

        $warehouse->delete();

        return response()->noContent();
    }
}
