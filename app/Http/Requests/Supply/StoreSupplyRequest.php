<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplyRequest extends FormRequest
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
            // إضافة التحقق من المخزن (كان مفقوداً)
            'warehouse_id'      => ['required', 'exists:warehouses,id'],

            // تصحيح: التحقق من معرف المورد بدلاً من الاسم النصي
            'supplier_id'       => ['required', 'exists:suppliers,id'],

            'sacrifice_type_id' => ['required', 'exists:sacrifice_types,id'],
            'quantity'          => ['required', 'integer', 'min:1'],
            'weight_note'       => ['nullable', 'string', 'max:255'],
            'total_value'       => ['nullable', 'integer', 'min:0'],
            'notes'             => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'warehouse_id.required'      => 'يجب تحديد المخزن المستلم.',
            'warehouse_id.exists'        => 'المخزن المحدد غير موجود.',

            'supplier_id.required'       => 'يجب تحديد المورد.',
            'supplier_id.exists'         => 'المورد المحدد غير موجود.',

            'sacrifice_type_id.required' => 'يجب تحديد نوع الأضحية.',
            'sacrifice_type_id.exists'   => 'نوع الأضحية المحدد غير موجود.',

            'quantity.required'          => 'الكمية مطلوبة.',
            'quantity.integer'           => 'الكمية يجب أن تكون رقماً صحيحاً.',
            'quantity.min'               => 'الكمية يجب أن تكون 1 على الأقل.',
            'total_value.integer'        => 'القيمة المالية يجب أن تكون رقماً صحيحاً.',
        ];
    }
}
