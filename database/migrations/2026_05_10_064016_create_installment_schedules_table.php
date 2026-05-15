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
        Schema::create('installment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_contract_id')->constrained('installment_contracts')->onDelete('cascade');
            $table->integer('amount'); // قيمة هذا القسط
            $table->date('due_date'); // تاريخ استحقاق القسط
            $table->boolean('is_paid')->default(false); // حالة الدفع
            // عند السداد، نربط هذا القسط المجدول بسجل الدفع الفعلي
            $table->foreignId('installment_payment_id')->nullable()->constrained('installment_payments')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installment_schedules');
    }
};
