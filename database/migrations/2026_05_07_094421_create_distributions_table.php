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

            // ربط التوزيع بالجهة التي يتبع لها المستخدم (تُجلب تلقائيًا في الخدمة)
            $table->foreignId('distribution_entity_id')->constrained('distribution_entities')->onDelete('restrict');

            $table->foreignId('user_id')->constrained('users')->onDelete('restrict'); // الموظف المنفذ
            $table->foreignId('beneficiary_id')->constrained('beneficiaries')->onDelete('restrict');
            $table->foreignId('sacrifice_type_id')->constrained('sacrifice_types')->onDelete('restrict');

            $table->enum('payment_method', ['free', 'cash', 'installments']);
            $table->integer('actual_price')->default(0); // السعر الفعلي الإجمالي
            $table->integer('quantity')->default(1); // كمية الأضاحي المصروفة

            // المرفقات والملاحظات الاختيارية المتوافقة مع الـ Request والـ Service
            $table->string('beneficiary_image')->nullable();
            $table->string('beneficiary_document')->nullable();
            $table->text('notes')->nullable();

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
