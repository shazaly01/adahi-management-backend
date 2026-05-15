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
        Schema::table('inventory_movements', function (Blueprint $table) {
            // إضافة العمود بالنوع الافتراضي BigInt Unsigned ليتوافق مع جدول warehouses
            if (!Schema::hasColumn('inventory_movements', 'warehouse_id')) {
                $table->foreignId('warehouse_id')
                      ->nullable()
                      ->after('sacrifice_type_id')
                      ->constrained('warehouses')
                      ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });
    }
};
