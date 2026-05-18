<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Distribution extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'receipt_number',
        'distribution_entity_id', // تمت الإضافة لمنع خطأ Mass Assignment
        'user_id',
        'beneficiary_id',
        'sacrifice_type_id',
        'payment_method',
        'actual_price',
        'quantity',               // تمت الإضافة لدعم العدد
        'beneficiary_image',      // تمت الإضافة لمنع خطأ Mass Assignment
        'beneficiary_document',   // تمت الإضافة لمنع خطأ Mass Assignment
        'notes',
        'delivery_location',            // تمت الإضافة لمنع خطأ Mass Assignment
    ];

    protected $casts = [
        'receipt_number' => 'string',
        'actual_price' => 'integer',
        'quantity' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function sacrificeType(): BelongsTo
    {
        return $this->belongsTo(SacrificeType::class);
    }

    public function installmentContract(): HasOne
    {
        return $this->hasOne(InstallmentContract::class);
    }

    /**
     * ربط التوزيع بسجل الحركات كحركة خروج
     */
    public function inventoryMovements(): MorphMany
    {
        return $this->morphMany(InventoryMovement::class, 'reference');
    }
}
