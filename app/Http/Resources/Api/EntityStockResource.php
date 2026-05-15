<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntityStockResource extends JsonResource
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
            'distribution_entity_id' => (string) $this->distribution_entity_id,
            'sacrifice_type_id' => $this->sacrifice_type_id,
            'quantity' => (int) $this->quantity,
     // جلب العلاقات إذا تم تحميلها
            'distribution_entity' => new DistributionEntityResource($this->whenLoaded('distributionEntity')),
            // نفترض وجود مورد لنوع الأضحية، يمكنك تعديله إذا كان اسم الملف مختلفاً لديك
            'sacrifice_type' => new SacrificeTypeResource($this->whenLoaded('sacrificeType')),

            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
