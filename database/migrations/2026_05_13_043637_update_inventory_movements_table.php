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
            // 1. تغيير أمين العهدة ليصبح "جهة التوزيع" بدلاً من "المستخدم"
            $table->renameColumn('custodian_id', 'distribution_entity_id');

            // 2. إضافة حقل user_id لتوثيق "المستخدم الفعلي" الذي قام بتنفيذ الحركة
            // استخدمنا DECIMAL(18, 0) التزاماً بالقاعدة المعمارية للمعرفات
            $table->decimal('user_id', 18, 0)->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->renameColumn('distribution_entity_id', 'custodian_id');
            $table->dropColumn('user_id');
        });
    }
};
