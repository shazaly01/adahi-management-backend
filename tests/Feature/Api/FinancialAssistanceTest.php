<?php

namespace Tests\Feature\Api;

use App\Models\Beneficiary;
use App\Models\Treasury;
use App\Models\FinancialAssistance;
use App\Models\TreasuryTransaction;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;

class FinancialAssistanceTest extends ApiTestCase
{
    /** --- 1. اختبار عملية الصرف الناجحة وتأثيرها المالي --- **/

    public function test_creating_financial_assistance_updates_treasury_and_logs_transaction()
    {
        // تجهيز البيانات: مستفيد وخزينة بها 2000 دينار
        $beneficiary = Beneficiary::factory()->create();
        $treasury = Treasury::factory()->create(['balance' => 2000]);

        $data = [
            'beneficiary_id' => $beneficiary->id,
            'type' => 'medical', // مساعدة علاجية
            'request_date' => now()->format('Y-m-d'),
            'approved_amount' => 500,
            'treasury_id' => $treasury->id,
            'TransactionNo' => '770088009900', // DECIMAL(18, 0)
            'notes' => 'صرف مساعدة علاجية طارئة'
        ];

        $response = $this->postJson('/api/financial-assistances', $data);

        // التأكد من نجاح الطلب
        $response->assertStatus(201);

        // 1. التأكد من خصم المبلغ من الخزينة (2000 - 500 = 1500)
        $this->assertEquals(1500, $treasury->fresh()->balance);

        // 2. التأكد من تسجيل المساعدة في جدول المساعدات المالية
        $this->assertDatabaseHas('financial_assistances', [
            'beneficiary_id' => $beneficiary->id,
            'approved_amount' => 500
        ]);

        // 3. التأكد من تسجيل الحركة المالية بالرقم المرجعي الصحيح
        $this->assertDatabaseHas('treasury_transactions', [
            'TransactionNo' => '770088009900',
            'amount' => 500,
            'transaction_type' => 'withdrawal'
        ]);
    }

    /** --- 2. اختبار فشل الصرف عند نقص الرصيد --- **/

    public function test_cannot_create_financial_assistance_with_insufficient_balance()
    {
        $beneficiary = Beneficiary::factory()->create();
        $treasury = Treasury::factory()->create(['balance' => 100]); // رصيد ضعيف

        $data = [
            'beneficiary_id' => $beneficiary->id,
            'type' => 'social',
            'request_date' => now()->format('Y-m-d'),
            'approved_amount' => 1000, // مبلغ أكبر من الرصيد
            'treasury_id' => $treasury->id,
            'TransactionNo' => '112233445566'
        ];

        $response = $this->postJson('/api/financial-assistances', $data);

        // النظام يجب أن يعيد خطأ (بسبب Exception في الـ Service)
        $response->assertStatus(500);

        // التأكد أن الرصيد لم يتأثر
        $this->assertEquals(100, $treasury->fresh()->balance);
    }

    /** --- 3. اختبار الصلاحيات والتحقق --- **/

    public function test_auditor_cannot_create_financial_assistance()
    {
        Sanctum::actingAs($this->auditorUser);

        $response = $this->postJson('/api/financial-assistances', []);

        // يجب أن يرفض قبل حتى أن يفحص البيانات
        $response->assertStatus(403);
    }

    public function test_financial_assistance_requires_valid_beneficiary_and_treasury()
    {
        $data = [
            'beneficiary_id' => 9999, // غير موجود
            'treasury_id' => 8888,    // غير موجود
            'approved_amount' => 100,
            'TransactionNo' => '123'
        ];

        $response = $this->postJson('/api/financial-assistances', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['beneficiary_id', 'treasury_id']);
    }
}
