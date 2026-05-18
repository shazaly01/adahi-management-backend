<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('distributions', function (Blueprint $table) {
            // إضافة حقل حالة التسليم كقيمة افتراضية (غير مسلم)
            $table->boolean('is_delivered')->default(false)->after('quantity');

            // إضافة حقل تاريخ ووقت التسليم (يقبل قيمة فارغة لأنه لن يسجل إلا عند التسليم الفعلي)
            $table->dateTime('delivery_date')->nullable()->after('is_delivered');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distributions', function (Blueprint $table) {
            // التراجع عن الإضافة في حال عمل rollback
            $table->dropColumn(['is_delivered', 'delivery_date']);
        });
    }
};
