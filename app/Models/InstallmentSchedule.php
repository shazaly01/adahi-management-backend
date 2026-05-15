<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'installment_contract_id',
        'amount',
        'due_date',
        'is_paid',
        'installment_payment_id',
    ];

    protected $casts = [
        'amount' => 'integer',
        'due_date' => 'date',
        'is_paid' => 'boolean',
    ];

    /**
     * العقد الأساسي الذي يتبعه هذا القسط المجدول
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(InstallmentContract::class, 'installment_contract_id');
    }

    /**
     * السداد الفعلي الذي تم لتغطية هذا القسط (إن وجد)
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(InstallmentPayment::class, 'installment_payment_id');
    }
}
