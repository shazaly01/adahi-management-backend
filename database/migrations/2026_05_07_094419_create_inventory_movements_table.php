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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sacrifice_type_id')->constrained('sacrifice_types')->onDelete('cascade');
            // الحامل للعهدة، إذا كان null فهذا يعني أنه في المخزن الرئيسي (الإدارة)
            $table->foreignId('custodian_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('movement_type', ['in', 'out']);
            $table->integer('quantity');

            // لتحديد نوع المستند الأصلي (توريد، تسليم عهدة، توزيع) ورقمه
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
