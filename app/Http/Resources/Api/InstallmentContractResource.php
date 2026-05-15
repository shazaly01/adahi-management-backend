<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentContractResource extends JsonResource
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
            'distribution_id' => $this->distribution_id,
            'beneficiary' => new BeneficiaryResource($this->whenLoaded('beneficiary')),
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'remaining_amount' => $this->total_amount - $this->paid_amount,
            'status' => $this->status,
            'payments' => InstallmentPaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
