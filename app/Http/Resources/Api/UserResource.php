<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'distribution_entity_id' => $this->distribution_entity_id,
            'full_name' => $this->full_name,
            'username' => $this->username,
            'email' => $this->email,
            'created_at' => $this->created_at->toDateTimeString(),

            // بيانات جهة التوزيع (تحمل فقط عند الطلب)
            'distribution_entity' => $this->whenLoaded('distributionEntity', function() {
                return [
                    'id' => $this->distributionEntity->id,
                    'name' => $this->distributionEntity->name,
                    'region' => $this->distributionEntity->region,
                ];
            }),

            // الأدوار
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
        ];
    }
}
