<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryDistributionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // نُرجع البيانات الأساسية فقط لضمان سرعة الاستجابة على هواتف الموزعين
        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'quantity' => $this->quantity,
            'is_delivered' => $this->is_delivered,
            'delivery_date' => $this->delivery_date ? $this->delivery_date->format('Y-m-d H:i:s') : null,

            // بيانات المستفيد
            'beneficiary' => [
                'id' => $this->beneficiary->id ?? null,
                'name' => $this->beneficiary->name ?? 'غير محدد',
                'phone' => $this->beneficiary->phone ?? 'لا يوجد',
                'national_id' => $this->beneficiary->national_id ?? 'لا يوجد',
            ],

            // بيانات الأضحية
            'sacrifice_type' => [
                'id' => $this->sacrificeType->id ?? null,
                'name' => $this->sacrificeType->name ?? 'غير محدد',
            ],
        ];
    }
}
