<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AllocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // بيانات جهة التوزيع المستلمة
            'distribution_entity_id' => $this->distribution_entity_id,
            'distribution_entity'    => [
                'id'   => $this->distributionEntity?->id,
                'name' => $this->distributionEntity?->name,
            ],

            // بيانات المخزن المصدر
            'warehouse_id' => $this->warehouse_id,
            'warehouse'    => [
                'id'   => $this->warehouse?->id,
                'name' => $this->warehouse?->name,
            ],

            // بيانات نوع الأضحية
            'sacrifice_type_id' => $this->sacrifice_type_id,
            'sacrifice_type'    => new SacrificeTypeResource($this->whenLoaded('sacrificeType')),

            'quantity'   => $this->quantity,
            'value'      => $this->value,
            'notes'      => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
