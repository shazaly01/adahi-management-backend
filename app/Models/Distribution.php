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
        'group',
        'distribution_entity_id',
        'user_id',
        'beneficiary_id',
        'sacrifice_type_id',
        'payment_method',
        'actual_price',
        'quantity',
        'beneficiary_image',
        'beneficiary_document',
        'notes',
        'delivery_location',
        'is_delivered',
        'delivery_date',
    ];

    protected $casts = [
        'receipt_number' => 'string',
        'group' => 'string',
        'actual_price' => 'integer',
        'quantity' => 'integer',
        'is_delivered' => 'boolean',
        'delivery_date' => 'datetime',
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

    public function inventoryMovements(): MorphMany
    {
        return $this->morphMany(InventoryMovement::class, 'reference');
    }

    // --- تم إضافة هذه العلاقة لحل الخطأ ---
    public function distributionEntity(): BelongsTo
    {
        return $this->belongsTo(DistributionEntity::class, 'distribution_entity_id');
    }
}
