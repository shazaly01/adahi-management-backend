<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Allocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'distribution_entity_id',
        'warehouse_id',
        'sacrifice_type_id',
        'quantity',
        'value',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'value'    => 'integer',
    ];

    public function distributionEntity(): BelongsTo
    {
        return $this->belongsTo(DistributionEntity::class, 'distribution_entity_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function sacrificeType(): BelongsTo
    {
        return $this->belongsTo(SacrificeType::class);
    }

    public function inventoryMovements(): MorphMany
    {
        return $this->morphMany(InventoryMovement::class, 'reference');
    }
}
