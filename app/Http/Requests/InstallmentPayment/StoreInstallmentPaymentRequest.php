<?php

namespace App\Http\Requests\InstallmentPayment;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstallmentPaymentRequest extends FormRequest
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
            'installment_contract_id' => ['required', 'exists:installment_contracts,id'],
            'amount' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'installment_contract_id.required' => 'يجب تحديد عقد الأقساط.',
            'installment_contract_id.exists' => 'عقد الأقساط المحدد غير موجود.',
            'amount.required' => 'مبلغ الدفعة مطلوب.',
            'amount.integer' => 'المبلغ يجب أن يكون رقماً صحيحاً.',
            'amount.min' => 'يجب أن يكون مبلغ الدفعة 1 على الأقل.',
        ];
    }
}
