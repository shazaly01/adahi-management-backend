<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'receipt_number',
        'installment_contract_id',
        'collected_by',
        'amount',
    ];

    protected $casts = [
        'receipt_number' => 'string',
        'amount' => 'integer',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(InstallmentContract::class, 'installment_contract_id');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }
}
