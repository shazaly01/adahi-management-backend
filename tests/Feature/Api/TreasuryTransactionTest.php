<?php

namespace Tests\Feature\Api;

use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;

class TreasuryTransactionTest extends ApiTestCase
{
    /** --- 1. اختبار الإيداع اليدوي --- **/

    public function test_can_deposit_money_manually_into_treasury()
    {
        $treasury = Treasury::factory()->create(['balance' => 1000]);

        $data = [
            'treasury_id'      => $treasury->id,
            'transaction_type' => 'deposit',
            'amount'           => 500,
            'TransactionNo'    => '99008800770066', // DECIMAL(18, 0)
            'transaction_date' => now()->format('Y-m-d'),
            'notes'            => 'إيداع من متبرع فاعل خير'
        ];

        $response = $this->postJson('/api/treasury-transactions', $data);

        $response->assertStatus(201);

        // التأكد من زيادة الرصيد (1000 + 500 = 1500)
        $this->assertEquals(1500, $treasury->fresh()->balance);

        $this->assertDatabaseHas('treasury_transactions', [
            'TransactionNo' => '99008800770066',
            'transaction_type' => 'deposit'
        ]);
    }

    /** --- 2. اختبار السحب اليدوي والتحقق من الرصيد --- **/

    public function test_cannot_withdraw_manually_if_balance_is_insufficient()
    {
        $treasury = Treasury::factory()->create(['balance' => 300]);

        $data = [
            'treasury_id'      => $treasury->id,
            'transaction_type' => 'withdrawal',
            'amount'           => 1000, // أكبر من الرصيد
            'TransactionNo'    => '111222333444',
            'transaction_date' => now()->format('Y-m-d')
        ];

        $response = $this->postJson('/api/treasury-transactions', $data);

        // يجب أن يرجع خطأ في الخادم (بسبب الـ Exception في السيرفس)
        $response->assertStatus(500);
        $this->assertEquals(300, $treasury->fresh()->balance);
    }

    /** --- 3. اختبار كشف حساب الخزينة (Statement) --- **/

    public function test_can_get_treasury_account_statement()
    {
        $treasury = Treasury::factory()->create();

        // إنشاء 3 حركات مختلفة لهذه الخزينة
        TreasuryTransaction::factory()->create(['treasury_id' => $treasury->id, 'amount' => 100]);
        TreasuryTransaction::factory()->create(['treasury_id' => $treasury->id, 'amount' => 200]);

        // حركة لخزينة أخرى (يجب ألا تظهر في التقرير)
        TreasuryTransaction::factory()->create(['amount' => 500]);

        $response = $this->getJson("/api/treasuries/{$treasury->id}/statement");

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data'); // يجب أن نجد حركتين فقط
    }

    /** --- 4. اختبار فرادة رقم المعاملة (Unique TransactionNo) --- **/

    public function test_transaction_no_must_be_unique()
    {
        TreasuryTransaction::factory()->create(['TransactionNo' => '5555555555']);

        $treasury = Treasury::factory()->create();
        $data = [
            'treasury_id'      => $treasury->id,
            'transaction_type' => 'deposit',
            'amount'           => 100,
            'TransactionNo'    => '5555555555', // مكرر
            'transaction_date' => now()->format('Y-m-d')
        ];

        $response = $this->postJson('/api/treasury-transactions', $data);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['TransactionNo']);
    }
}
