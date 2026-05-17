<?php

namespace App\Http\Requests\DistributionEntity;

use Illuminate\Foundation\Http\FormRequest;

class StoreDistributionEntityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // الصلاحيات تتم معالجتها في الـ Controller عبر الـ Policy
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
            'name' => [
                'required',
                'string',
                'max:255'
            ],
            'region' => [
                'nullable',
                'string',
                'max:255'
            ],
            'is_active' => [
                'nullable',
                'boolean'
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'اسم الجهة',
            'region' => 'المنطقة',
            'is_active' => 'حالة التفعيل',
        ];
    }
}
