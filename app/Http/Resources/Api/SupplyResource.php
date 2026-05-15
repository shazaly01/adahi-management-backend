<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplyResource extends JsonResource
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
            'supplier_name' => $this->supplier_name,
            'sacrifice_type_id' => $this->sacrifice_type_id,
            'sacrifice_type' => new SacrificeTypeResource($this->whenLoaded('sacrificeType')),
            'quantity' => $this->quantity,
            'weight_note' => $this->weight_note,
            'total_value' => $this->total_value,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
