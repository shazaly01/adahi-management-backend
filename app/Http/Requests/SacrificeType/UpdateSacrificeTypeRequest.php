<?php

namespace App\Http\Requests\SacrificeType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSacrificeTypeRequest extends FormRequest
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
        // التحقق مما إذا كان الطلب يحتوي على حقل base_price
        // وتعيينه إلى 0 في حال كان فارغاً ليتوافق مع الواجهة الأمامية
        if ($this->has('base_price')) {
            $this->merge([
                'base_price' => $this->filled('base_price') ? $this->input('base_price') : 0,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $sacrificeType = $this->route('sacrifice_type');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('sacrifice_types', 'name')->ignore($sacrificeType)
            ],
            'base_price' => ['sometimes', 'required', 'integer', 'min:0'],
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
