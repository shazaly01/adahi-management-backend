<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BeneficiaryResource extends JsonResource
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
            'user_id' => $this->user_id, // معرّف الجهة الموزعة التي أضافته
            'distributor_name' => $this->whenLoaded('user', fn() => $this->user->full_name), // اسم الجهة الموزعة (إذا تم طلبه مع العلاقات)
            'name' => $this->name,
            'national_id' => (string) $this->national_id,
            'phone' => $this->phone, // حقل الهاتف الجديد
            'job_number' => $this->job_number,
            'address' => $this->address,
            'distributions_count' => $this->whenCounted('distributions'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
