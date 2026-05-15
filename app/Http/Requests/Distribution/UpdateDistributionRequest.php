<?php

namespace App\Http\Requests\Distribution;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDistributionRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'beneficiary_id' => ['sometimes', 'required', 'exists:beneficiaries,id'],
            'sacrifice_type_id' => ['sometimes', 'required', 'exists:sacrifice_types,id'],
            'payment_method' => ['sometimes', 'required', 'in:free,cash,installments'],
            'actual_price' => ['sometimes', 'required_unless:payment_method,free', 'integer', 'min:0'],

            // في حالة التعديل، المرفقات اختيارية (يتم رفعها فقط إذا أراد المستخدم تغييرها)
            'beneficiary_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:4096'],
            'beneficiary_document' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:4096'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('payment_method') && $this->payment_method === 'free') {
            $this->merge([
                'actual_price' => 0,
            ]);
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'beneficiary_id.required' => 'يجب تحديد المستفيد.',
            'sacrifice_type_id.required' => 'يجب تحديد نوع الأضحية.',
            'payment_method.required' => 'طريقة الدفع مطلوبة.',
            'actual_price.required_unless' => 'السعر الفعلي مطلوب لهذه الطريقة من الدفع.',
            'actual_price.integer' => 'السعر الفعلي يجب أن يكون رقماً صحيحاً.',
            'beneficiary_image.image' => 'يجب أن يكون الملف المرفق صورة.',
        ];
    }
}
