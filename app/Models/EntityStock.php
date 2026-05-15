<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityStock extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'distribution_entity_id',
        'sacrifice_type_id',
        'quantity',
    ];

    protected $casts = [
        'distribution_entity_id' => 'string',
        'quantity' => 'integer',
    ];

    public function distributionEntity(): BelongsTo
    {
        return $this->belongsTo(DistributionEntity::class);
    }

    public function sacrificeType(): BelongsTo
    {
        return $this->belongsTo(SacrificeType::class);
    }
}
