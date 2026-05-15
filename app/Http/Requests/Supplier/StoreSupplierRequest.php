<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
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
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
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
            'name.required' => 'اسم المورد مطلوب.',
            'name.string' => 'اسم المورد يجب أن يكون نصاً.',
            'name.max' => 'اسم المورد يجب ألا يتجاوز 255 حرفاً.',
            'phone.string' => 'رقم الهاتف يجب أن يكون نصاً صالحاً.',
            'phone.max' => 'رقم الهاتف يجب ألا يتجاوز 20 حرفاً.',
            'address.string' => 'العنوان يجب أن يكون نصاً صالحاً.',
            'address.max' => 'العنوان يجب ألا يتجاوز 500 حرف.',
            'is_active.boolean' => 'حالة التفعيل يجب أن تكون صحيحة أو خاطئة (True/False).',
        ];
    }
}
