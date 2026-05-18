<?php

namespace App\Http\Requests\Allocation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAllocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'distribution_entity_id' => ['sometimes', 'required', 'exists:distribution_entities,id'],
            'warehouse_id'           => ['sometimes', 'required', 'exists:warehouses,id'], // تمت إضافته للسماح بتعديل المخزن
            'sacrifice_type_id'      => ['sometimes', 'required', 'exists:sacrifice_types,id'],
            'quantity'               => ['sometimes', 'required', 'integer', 'min:1'],
            'value'                  => ['nullable', 'integer', 'min:0'],
            'notes'                  => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'distribution_entity_id.required' => 'يجب تحديد جهة التوزيع المستلمة للعهدة.',
            'distribution_entity_id.exists'   => 'جهة التوزيع المحددة غير موجودة.',
            'warehouse_id.required'           => 'يجب تحديد المخزن المصدر.',
            'warehouse_id.exists'             => 'المخزن المحدد غير موجود.',
            'sacrifice_type_id.required'      => 'يجب تحديد نوع الأضحية.',
            'sacrifice_type_id.exists'        => 'نوع الأضحية المحدد غير موجود.',
            'quantity.required'               => 'الكمية مطلوبة.',
            'quantity.integer'                => 'الكمية يجب أن تكون رقماً صحيحاً.',
            'quantity.min'                    => 'الكمية يجب أن تكون 1 على الأقل.',
            'value.integer'                   => 'القيمة المالية يجب أن تكون رقماً صحيحاً.',
        ];
    }
}
