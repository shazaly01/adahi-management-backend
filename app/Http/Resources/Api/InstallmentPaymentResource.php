<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentPaymentResource extends JsonResource
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
            'receipt_number' => (string) $this->receipt_number,
            'amount' => $this->amount,
            'collected_by' => $this->whenLoaded('collector', function() {
                return [
                    'id' => $this->collector->id,
                    'full_name' => $this->collector->full_name,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
