<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, HasApiTokens;

    protected string $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'distribution_entity_id',
        'full_name',
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'distribution_entity_id' => 'string',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * جهة التوزيع التي يتبع لها هذا المستخدم
     */
    public function distributionEntity(): BelongsTo
    {
        return $this->belongsTo(DistributionEntity::class);
    }

    /**
     * السجل الدقيق لحركات المخزون التي قام بها هذا المستخدم الفعلي
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'user_id');
    }

    /**
     * عمليات تسليم العهد التي استلمها هذا المستخدم
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    /**
     * عمليات التوزيع النهائي التي قام بها هذا المستخدم للمستفيدين
     */
    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class);
    }

    /**
     * دفعات الأقساط التي قام هذا المستخدم بتحصيلها
     */
    public function collectedInstallments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class, 'collected_by');
    }
}
