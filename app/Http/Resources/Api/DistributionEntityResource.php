<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DistributionEntityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'region' => $this->region,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,

            // يمكنك لاحقاً جلب العلاقات إذا تم طلبها عبر with()
            // 'users' => UserResource::collection($this->whenLoaded('users')),
            // 'stocks' => EntityStockResource::collection($this->whenLoaded('entityStocks')),
        ];
    }
}
