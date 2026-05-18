<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Beneficiary extends Model
{
    use HasFactory, SoftDeletes;

   protected $fillable = [
        'user_id',
        'name',
        'national_id',
        'phone',
        'job_number',
        'address',
    ];

    protected $casts = [
        'national_id' => 'string',
    ];

    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class);
    }

    public function installmentContracts(): HasMany
    {
        return $this->hasMany(InstallmentContract::class);
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
