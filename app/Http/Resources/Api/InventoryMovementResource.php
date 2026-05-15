<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'movement_type'  => $this->movement_type,
            'quantity'       => $this->quantity,
            'sacrifice_type' => $this->sacrificeType?->name ?? '---',
            // جلب اسم المخزن أو الجهة بناءً على نوع الحركة
            'warehouse_name' => $this->warehouse?->name ?? '---',
            'entity_name'    => $this->distributionEntity?->name ?? 'المخزن الرئيسي',
            'custodian_name' => $this->user?->full_name ?? $this->user?->name ?? 'النظام',
            'reference_type' => $this->reference_type,
            'reference_id'   => $this->reference_id,
            'created_at'     => $this->created_at->toDateTimeString(),
        ];
    }
}
