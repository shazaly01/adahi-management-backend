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
             * بناءً على منطق العمل المذكور في ملف SupplyForm.vue:
             * يتم السماح فقط بتعديل الملاحظات لضمان سلامة الأرصدة المخزنية.
             */
            'weight_note' => ['nullable', 'string', 'max:255'],
            'notes'       => ['nullable', 'string'],

            // في حال تم إرسال باقي الحقول، نتجاهلها أو نمنعها برمجياً
            // لضمان عدم تغيير الكمية أو المخزن أو المورد بعد الإدخال الأول.
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'weight_note.string' => 'ملاحظة الوزن يجب أن تكون نصاً.',
            'weight_note.max'    => 'ملاحظة الوزن يجب ألا تتجاوز 255 حرفاً.',
            'notes.string'       => 'الملاحظات يجب أن تكون نصاً.',
        ];
    }
}
