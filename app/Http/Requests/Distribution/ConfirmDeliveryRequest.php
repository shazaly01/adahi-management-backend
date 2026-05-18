<?php

namespace App\Http\Requests\Distribution;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Distribution;

class ConfirmDeliveryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // التحقق من الصلاحية باستخدام دالة deliver التي أضفناها في Policy
        return $this->user()->can('deliver', Distribution::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // حقل حالة التسليم إلزامي ويجب أن يكون قيمة منطقية (true/false)
            'is_delivered' => ['required', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'is_delivered.required' => 'يجب تحديد حالة التسليم.',
            'is_delivered.boolean' => 'حالة التسليم يجب أن تكون إما صح أو خطأ.',
        ];
    }
}
