<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SacrificeType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'base_price',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'integer',
        'is_active' => 'boolean',
    ];

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function supplies(): HasMany
    {
        return $this->hasMany(Supply::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class);
    }
}
