<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EntityStock;
use App\Http\Resources\Api\EntityStockResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EntityStockController extends Controller
{
    /**
     * عرض قائمة جميع الأرصدة المخزنية في النظام.
     */
    public function index(): AnonymousResourceCollection
    {
        // الجميع يستطيع الرؤية - جلب الأرصدة مع العلاقات الأساسية لتحسين الأداء
        $stocks = EntityStock::with(['sacrificeType', 'distributionEntity'])->latest()->get();

        return EntityStockResource::collection($stocks);
    }

    /**
     * عرض تفاصيل رصيد محدد.
     */
    public function show(EntityStock $entityStock): EntityStockResource
    {
        // الجميع يستطيع الرؤية
        $entityStock->load(['sacrificeType', 'distributionEntity']);

        return new EntityStockResource($entityStock);
    }
}
