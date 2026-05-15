<?php

namespace App\Http\Requests\SacrificeType;

use Illuminate\Foundation\Http\FormRequest;

class StoreSacrificeTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // تعيين السعر الأساسي إلى 0 في حال لم يتم إرساله أو كان فارغاً
        $this->merge([
            'base_price' => $this->filled('base_price') ? $this->input('base_price') : 0,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:sacrifice_types,name'],
            'base_price' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
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
            'name.required' => 'اسم النوع مطلوب.',
            'name.unique' => 'هذا النوع مسجل مسبقاً.',
            'base_price.required' => 'السعر الأساسي مطلوب.',
            'base_price.integer' => 'السعر الأساسي يجب أن يكون رقماً صحيحاً.',
        ];
    }
}
