<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Supply extends Model
{
    use HasFactory, SoftDeletes;

   protected $fillable = [
        'supplier_id',
        'warehouse_id',
        'sacrifice_type_id',
        'quantity',
        'weight_note',
        'total_value',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'total_value' => 'integer',
    ];

    public function sacrificeType(): BelongsTo
    {
        return $this->belongsTo(SacrificeType::class);
    }

    /**
     * ربط التوريد بسجل حركات المخزون كحركة دخول
     */
    public function inventoryMovements(): MorphMany
    {
        return $this->morphMany(InventoryMovement::class, 'reference');
    }


    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
