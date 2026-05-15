<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SacrificeType;
use App\Http\Requests\SacrificeType\StoreSacrificeTypeRequest;
use App\Http\Requests\SacrificeType\UpdateSacrificeTypeRequest;
use App\Http\Resources\Api\SacrificeTypeResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SacrificeTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SacrificeType::class);

        $types = SacrificeType::latest()->get();

        return SacrificeTypeResource::collection($types);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSacrificeTypeRequest $request): SacrificeTypeResource
    {
        $this->authorize('create', SacrificeType::class);

        $sacrificeType = SacrificeType::create($request->validated());

        return new SacrificeTypeResource($sacrificeType);
    }

    /**
     * Display the specified resource.
     */
    public function show(SacrificeType $sacrificeType): SacrificeTypeResource
    {
        $this->authorize('view', $sacrificeType);

        return new SacrificeTypeResource($sacrificeType);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSacrificeTypeRequest $request, SacrificeType $sacrificeType): SacrificeTypeResource
    {
        $this->authorize('update', $sacrificeType);

        $sacrificeType->update($request->validated());

        return new SacrificeTypeResource($sacrificeType);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SacrificeType $sacrificeType): Response
    {
        $this->authorize('delete', $sacrificeType);

        $sacrificeType->delete();

        return response()->noContent();
    }
}
