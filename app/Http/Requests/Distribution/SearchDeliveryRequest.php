<?php

namespace App\Http\Requests\Distribution;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Distribution;

class SearchDeliveryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // التحقق من الصلاحية باستخدام الدالة التي أضفناها للتو في الـ Policy
        return $this->user()->can('deliver', Distribution::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // حقل البحث شامل (للاسم، الهاتف، أو الرقم الوطني)
            'search_term' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'search_term.required' => 'يرجى إدخال كلمة البحث (الاسم، الهاتف، أو الرقم الوطني).',
            'search_term.string' => 'يجب أن يكون نص البحث عبارة عن حروف أو أرقام.',
            'search_term.min' => 'يجب ألا يقل نص البحث عن 3 أحرف لضمان دقة النتائج.',
            'search_term.max' => 'نص البحث طويل جداً.',
        ];
    }
}
