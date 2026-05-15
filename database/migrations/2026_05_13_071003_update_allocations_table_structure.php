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
    Schema::table('allocations', function (Blueprint $table) {
        // 1. حذف العمود القديم إذا وجد
        if (Schema::hasColumn('allocations', 'user_id')) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        }

        // 2. إضافة الأعمدة الجديدة بالنوع الافتراضي (BigInt Unsigned) والربط التلقائي
        $table->foreignId('distribution_entity_id')
              ->after('id')
              ->constrained('distribution_entities')
              ->onDelete('cascade');

        $table->foreignId('warehouse_id')
              ->after('distribution_entity_id')
              ->constrained('warehouses')
              ->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('allocations', function (Blueprint $table) {
            $table->dropForeign(['distribution_entity_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn(['distribution_entity_id', 'warehouse_id']);

            // إعادة الحقل القديم في حال التراجع
            $table->decimal('user_id', 18, 0)->nullable();
        });
    }
};
