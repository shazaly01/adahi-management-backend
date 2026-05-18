<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // الصلاحيات تُدار عبر الـ Policy كما هو متبع في بروتوكولك
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            /*
             * المعمارية الجديدة:
             * تم فتح جميع الحقول المؤثرة على المخزون (الكمية، المخزن، النوع)
             * لأن SupplyController و InventoryService أصبحا قادرين على
             * عمل (Soft Delete) للحركة القديمة وإنشاء حركة جديدة بأمان تام.
             */
            'warehouse_id'      => ['sometimes', 'required', 'exists:warehouses,id'],
            'supplier_id'       => ['sometimes', 'required', 'exists:suppliers,id'],
            'sacrifice_type_id' => ['sometimes', 'required', 'exists:sacrifice_types,id'],
            'quantity'          => ['sometimes', 'required', 'integer', 'min:1'],
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
            'weight_note.string'         => 'ملاحظة الوزن يجب أن تكون نصاً.',
            'weight_note.max'            => 'ملاحظة الوزن يجب ألا تتجاوز 255 حرفاً.',
            'total_value.integer'        => 'القيمة المالية يجب أن تكون رقماً صحيحاً.',
            'notes.string'               => 'الملاحظات يجب أن تكون نصاً.',
        ];
    }
}
