<?php

namespace Tests\Feature\Api;

use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;

class TreasuryTest extends ApiTestCase
{
    /** --- 1. اختبارات الوصول والعرض --- **/

    public function test_admin_can_list_treasuries()
    {
        Treasury::factory(2)->create();
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/treasuries');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data']);
    }

    /** --- 2. اختبارات الإنشاء والتعديل --- **/

    public function test_super_admin_can_create_treasury()
    {
        $data = [
            'name' => 'خزينة الصدقات الجارية',
            'balance' => 5000.50
        ];

        $response = $this->postJson('/api/treasuries', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('treasuries', ['name' => 'خزينة الصدقات الجارية']);
    }

    public function test_update_treasury_fails_if_name_is_duplicated()
    {
        Treasury::factory()->create(['name' => 'خزينة 1']);
        $treasury2 = Treasury::factory()->create(['name' => 'خزينة 2']);

        $response = $this->putJson("/api/treasuries/{$treasury2->id}", [
            'name' => 'خزينة 1', // اسم مكرر
            'balance' => 100
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** --- 3. اختبارات الحذف (قواعد العمل) --- **/

    public function test_cannot_delete_treasury_that_has_transactions()
    {
        $treasury = Treasury::factory()->create();

        // إنشاء حركة مالية مرتبطة بهذه الخزينة
        TreasuryTransaction::factory()->create([
            'treasury_id' => $treasury->id,
            'TransactionNo' => '1234567890'
        ]);

        $response = $this->deleteJson("/api/treasuries/{$treasury->id}");

        // يجب أن يرفض الحذف لأن الخزينة ليست فارغة
        $response->assertStatus(422)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'لا يمكن حذف خزينة تحتوي على حركات مالية');

        $this->assertDatabaseHas('treasuries', ['id' => $treasury->id]);
    }

    public function test_can_delete_empty_treasury()
    {
        $treasury = Treasury::factory()->create();

        $response = $this->deleteJson("/api/treasuries/{$treasury->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('treasuries', ['id' => $treasury->id]);
    }

    /** --- 4. اختبارات الصلاحيات --- **/

    public function test_data_entry_cannot_delete_treasury()
    {
        Sanctum::actingAs($this->dataEntryUser);
        $treasury = Treasury::factory()->create();

        $this->deleteJson("/api/treasuries/{$treasury->id}")->assertStatus(403);
    }
}
