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
        // الصلاحيات تتم معالجتها في الـ Policy
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
            // استخدمنا sometimes للسماح بالتحديث الجزئي (Patch) أو الكلي (Put)
            'beneficiary_id' => ['sometimes', 'required', 'exists:beneficiaries,id'],
            'sacrifice_type_id' => ['sometimes', 'required', 'exists:sacrifice_types,id'],
            'payment_method' => ['sometimes', 'required', 'in:free,cash,installments'],

            // السعر الفعلي مطلوب إذا لم يكن معفى
            'actual_price' => ['required_unless:payment_method,free', 'integer', 'min:0'],

            // عدد أشهر التقسيط إلزامي فقط إذا كان نوع الدفع أقساط
            'months_count' => ['required_if:payment_method,installments', 'integer', 'min:1'],

            // الكمية والملاحظات والمرفقات
            'quantity' => ['nullable', 'integer', 'min:1'],
            'beneficiary_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:4096'],
            'beneficiary_document' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:4096'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'delivery_location' => ['nullable', 'string', 'max:255'],
            'delivery_date' => ['required_if:is_delivered,true', 'nullable', 'date'],
            'group' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // إذا كان مجانياً، نجبر السعر على أن يكون 0 لتجنب أي أخطاء مدخلات
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
            'beneficiary_id.exists' => 'المستفيد المحدد غير موجود.',
            'sacrifice_type_id.required' => 'يجب تحديد نوع الأضحية.',
            'sacrifice_type_id.exists' => 'نوع الأضحية المحدد غير موجود.',
            'payment_method.required' => 'طريقة الدفع مطلوبة.',
            'payment_method.in' => 'طريقة الدفع غير صالحة.',
            'actual_price.required_unless' => 'السعر الفعلي مطلوب لهذه الطريقة من الدفع.',
            'actual_price.integer' => 'السعر الفعلي يجب أن يكون رقماً صحيحاً.',
            'months_count.required_if' => 'عدد أشهر التقسيط مطلوب عندما تكون طريقة الدفع أقساط.',
            'months_count.integer' => 'عدد الأشهر يجب أن يكون رقماً صحيحاً.',
            'months_count.min' => 'عدد الأشهر يجب أن يكون شهراً واحداً على الأقل.',
            'quantity.integer' => 'الكمية يجب أن تكون رقماً صحيحاً.',
            'quantity.min' => 'يجب ألا تقل الكمية عن أضحية واحدة.',
            'beneficiary_image.image' => 'يجب أن يكون الملف المرفق صورة.',
            'beneficiary_image.mimes' => 'صيغة الصورة غير مدعومة.',
            'beneficiary_document.file' => 'يجب أن يكون المرفق ملفاً صالحاً.',
        ];
    }
}
