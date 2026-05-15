<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
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
            'location' => ['nullable', 'string', 'max:500'],
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
            'name.required' => 'اسم المخزن مطلوب.',
            'name.string' => 'اسم المخزن يجب أن يكون نصاً.',
            'name.max' => 'اسم المخزن يجب ألا يتجاوز 255 حرفاً.',
            'location.string' => 'الموقع يجب أن يكون نصاً صالحاً.',
            'location.max' => 'الموقع يجب ألا يتجاوز 500 حرف.',
            'is_active.boolean' => 'حالة التفعيل يجب أن تكون صحيحة أو خاطئة (True/False).',
        ];
    }
}
