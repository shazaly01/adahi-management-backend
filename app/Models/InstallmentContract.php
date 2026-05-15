<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallmentContract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'distribution_id',
        'beneficiary_id',
        'total_amount',
        'paid_amount',
        'status',
    ];

    protected $casts = [
        'total_amount' => 'integer',
        'paid_amount' => 'integer',
    ];

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class);
    }


    public function schedules(): HasMany
    {
        return $this->hasMany(InstallmentSchedule::class);
    }
}
