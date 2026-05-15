<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DistributionEntity extends Model
{
    use HasFactory, SoftDeletes;

    // تمت إزالة تعريفات DECIMAL يدوياً ليعود للوضع الافتراضي (Auto-increment BigInt)

    protected $fillable = [
        'name',
        'region',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function entityStocks(): HasMany
    {
        return $this->hasMany(EntityStock::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'distribution_entity_id');
    }
}
