<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sacrifice_type_id',
        'warehouse_id',           // الحقل المفقود الذي تسبب في "الرصيد الصفر"
        'distribution_entity_id',
        'user_id',
        'movement_type',          // 'in' (توريد) أو 'out' (صرف)
        'quantity',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        // تم حذف تحويل المعرفات لنصوص للالتزام بالنوع الافتراضي BigInt
        'quantity' => 'integer',
    ];

    /**
     * العلاقة مع نوع الأضحية
     */
    public function sacrificeType(): BelongsTo
    {
        return $this->belongsTo(SacrificeType::class);
    }

    /**
     * العلاقة مع المخزن المرتبط بهذه الحركة (توريد أو سحب عهدة)
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * جهة التوزيع التي تملك عهدة هذه الحركة (في حالة الصرف للجهات)
     */
    public function distributionEntity(): BelongsTo
    {
        return $this->belongsTo(DistributionEntity::class, 'distribution_entity_id');
    }

    /**
     * المستخدم الفعلي الذي قام بتنفيذ وإدخال هذه الحركة في النظام
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * العلاقة متعددة الأشكال لجلب المستند الأصلي للحركة (Supply أو Allocation)
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
