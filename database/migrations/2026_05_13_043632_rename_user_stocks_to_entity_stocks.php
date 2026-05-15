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
        // 1. تغيير اسم الجدول ليعكس المعمارية الجديدة
        Schema::rename('user_stocks', 'entity_stocks');

        // 2. تغيير اسم العمود من user_id إلى distribution_entity_id
        Schema::table('entity_stocks', function (Blueprint $table) {
            $table->renameColumn('user_id', 'distribution_entity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entity_stocks', function (Blueprint $table) {
            $table->renameColumn('distribution_entity_id', 'user_id');
        });

        Schema::rename('entity_stocks', 'user_stocks');
    }
};
