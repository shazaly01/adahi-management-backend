<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class BeneficiaryStatementRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'beneficiary_id' => 'required|exists:beneficiaries,id',
        ];
    }
}
