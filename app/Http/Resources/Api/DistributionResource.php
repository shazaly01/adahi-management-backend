<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DistributionResource extends JsonResource
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
            'group' => $this->group,
            'user_id' => $this->user_id,
            'distributor' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'full_name' => $this->user->full_name,
                ];
            }),
            'beneficiary_id' => $this->beneficiary_id,
            'beneficiary' => new BeneficiaryResource($this->whenLoaded('beneficiary')),
            'sacrifice_type_id' => $this->sacrifice_type_id,
            'sacrifice_type' => new SacrificeTypeResource($this->whenLoaded('sacrificeType')),
            'payment_method' => $this->payment_method,
            'actual_price' => $this->actual_price,

            // التعديل الأساسي: تمرير الكمية للواجهة الأمامية
            'quantity' => $this->quantity,

            // تمرير الملاحظات أيضاً لتظهر في تفاصيل الإيصال إذا لزم الأمر
            'notes' => $this->notes,
            'delivery_location' => $this->delivery_location,
            'is_delivered' => (bool) $this->is_delivered,
             'delivery_date' => $this->delivery_date?->format('Y-m-d H:i:s'),

            // إضافة مسارات Mرفقات لتظهر كروابط مباشرة في الواجهة
            'beneficiary_image' => $this->beneficiary_image ? asset('storage/' . $this->beneficiary_image) : null,
            'beneficiary_document' => $this->beneficiary_document ? asset('storage/' . $this->beneficiary_document) : null,

            // إضافة بيانات العقد إذا كان الدفع أقساطاً وتم تحميل العلاقة
            'installment_contract' => $this->whenLoaded('installmentContract', function () {
                return [
                    'id' => $this->installmentContract->id,
                    'total_amount' => $this->installmentContract->total_amount,
                    'paid_amount' => $this->installmentContract->paid_amount,
                    'status' => $this->installmentContract->status,
                ];
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
