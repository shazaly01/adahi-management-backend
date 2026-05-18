<?php

namespace App\Http\Requests\Beneficiary;

use Illuminate\Foundation\Http\FormRequest;

class StoreBeneficiaryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            // التعديل هنا: تغييره إلى nullable للسماح بإدخال الجهات والمشاريع
            'national_id' => ['nullable', 'numeric', 'digits_between:1,18', 'unique:beneficiaries,national_id'],
            'phone' => ['required', 'string', 'max:20'],
            'job_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
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
            'name.required' => 'اسم المستفيد مطلوب.',
            'national_id.numeric' => 'الرقم الوطني يجب أن يحتوي على أرقام فقط.',
            'national_id.digits_between' => 'الرقم الوطني لا يمكن أن يتجاوز 18 خانة.',
            'national_id.unique' => 'هذا المستفيد (أو الرقم الوطني) مسجل في النظام مسبقاً.',
            'phone.required' => 'رقم هاتف المستفيد مطلوب.',
            'phone.string' => 'رقم الهاتف يجب أن يكون نصاً صالحاً.',
            'phone.max' => 'رقم الهاتف لا يمكن أن يتجاوز 20 خانة.',
        ];
    }
}
