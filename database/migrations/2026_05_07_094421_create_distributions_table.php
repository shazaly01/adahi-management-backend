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
        Schema::create('distributions', function (Blueprint $table) {
            $table->id();
            $table->decimal('receipt_number', 18, 0)->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict'); // الموزع
            $table->foreignId('beneficiary_id')->constrained('beneficiaries')->onDelete('restrict');
            $table->foreignId('sacrifice_type_id')->constrained('sacrifice_types')->onDelete('restrict');
            $table->enum('payment_method', ['free', 'cash', 'installments']);
            $table->integer('actual_price')->default(0); // السعر الفعلي (0 في حالة المجاني)
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributions');
    }
};
