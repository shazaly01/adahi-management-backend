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
        'user_id',
        'beneficiary_id',
        'sacrifice_type_id',
        'payment_method',
        'actual_price',
    ];

    protected $casts = [
        'receipt_number' => 'string',
        'actual_price' => 'integer',
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
