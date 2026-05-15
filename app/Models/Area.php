<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    protected $fillable = ['name', 'parent_id'];

    // الوصول للمنطقة "الأب" (مثلاً الوصول للمحافظة من خلال الحي)
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'parent_id');
    }

    // الوصول لـ "الأبناء" (مثلاً عرض كل الأحياء التابعة لمحافظة)
   public function children()
{
    // السحر هنا: كلما تم جلب ابن، سيقوم تلقائياً بجلب أبنائه هو أيضاً!
    return $this->hasMany(Area::class, 'parent_id')->with('children');
}


    /**
 * دالة تجلب المسار الكامل للمنطقة (مثلاً: الرياض > العليا > شارع التحلية)
 */
public function getFullPathAttribute(): string
{
    $path = [$this->name];
    $parent = $this->parent;

    // استمرار الصعود للأعلى طالما يوجد أب
    while ($parent) {
        array_unshift($path, $parent->name); // إضافة الاسم في بداية المصفوفة
        $parent = $parent->parent;
    }

    return implode(' > ', $path);
}
}
