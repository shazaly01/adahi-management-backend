<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class TreasuryStatementRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'treasury_id' => 'required|exists:treasuries,id',
            'from_date'   => 'required|date',
            'to_date'     => 'required|date|after_or_equal:from_date',
        ];
    }
}
