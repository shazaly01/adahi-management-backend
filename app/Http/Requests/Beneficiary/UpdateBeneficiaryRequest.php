<?php

namespace App\Http\Requests\Beneficiary;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBeneficiaryRequest extends FormRequest
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
        $beneficiary = $this->route('beneficiary');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'national_id' => [
                'sometimes',
                'required',
                'numeric',
                'digits_between:1,18',
                Rule::unique('beneficiaries', 'national_id')->ignore($beneficiary)
            ],
            'job_number' => ['nullable', 'string', 'max:50'],
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
            'national_id.required' => 'الرقم الوطني مطلوب.',
            'national_id.numeric' => 'الرقم الوطني يجب أن يحتوي على أرقام فقط.',
            'national_id.digits_between' => 'الرقم الوطني لا يمكن أن يتجاوز 18 خانة.',
            'national_id.unique' => 'هذا المستفيد مسجل في النظام مسبقاً.',
        ];
    }
}
