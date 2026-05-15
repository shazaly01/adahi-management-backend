<?php

namespace Tests\Feature\Api;

use App\Models\Beneficiary;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;

class BeneficiaryTest extends ApiTestCase
{
    /** --- 1. اختبارات العرض (Read) --- **/

    public function test_authorized_users_can_list_beneficiaries()
    {
        Beneficiary::factory(3)->create();

        // تجربة بـ Auditor (يسمح له بالعرض فقط)
        Sanctum::actingAs($this->auditorUser);

        $response = $this->getJson('/api/beneficiaries');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data', 'meta'])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_show_single_beneficiary_details()
    {
        $beneficiary = Beneficiary::factory()->create(['name' => 'أحمد علي']);

        $response = $this->getJson("/api/beneficiaries/{$beneficiary->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'أحمد علي');
    }

    /** --- 2. اختبارات الإنشاء (Create) --- **/

    public function test_data_entry_can_create_beneficiary()
    {
        Sanctum::actingAs($this->dataEntryUser);

        $data = [
            'name' => 'مستفيد جديد',
            'national_id' => '123456789012',
            'family_members_count' => 5,
            'gender' => 'ذكر'
        ];

        $response = $this->postJson('/api/beneficiaries', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('beneficiaries', ['national_id' => '123456789012']);
    }

    public function test_create_beneficiary_fails_validation_if_national_id_exists()
    {
        Beneficiary::factory()->create(['national_id' => '111111111111']);

        $data = [
            'name' => 'اسم مكرر',
            'national_id' => '111111111111', // مكرر
            'family_members_count' => 2
        ];

        $response = $this->postJson('/api/beneficiaries', $data);

        $response->assertStatus(422) // Unprocessable Entity
            ->assertJsonValidationErrors(['national_id']);
    }

    /** --- 3. اختبارات التحديث (Update) --- **/

    public function test_can_update_beneficiary_while_ignoring_its_own_national_id()
    {
        $beneficiary = Beneficiary::factory()->create(['national_id' => '222222222222']);

        $updateData = [
            'name' => 'اسم معدل',
            'national_id' => '222222222222', // نفس الرقم القديم، يجب ألا يرفض
            'family_members_count' => 10
        ];

        $response = $this->putJson("/api/beneficiaries/{$beneficiary->id}", $updateData);

        $response->assertStatus(200);
        $this->assertEquals('اسم معدل', $beneficiary->fresh()->name);
    }

    /** --- 4. اختبارات الصلاحيات (Authorization) --- **/

  public function test_auditor_is_forbidden_from_creating_or_deleting()
    {
        Sanctum::actingAs($this->auditorUser);

        // محاولة إنشاء ببيانات مكتملة لضمان تجاوز الـ Validation والوصول للـ Policy
        $data = [
            'name' => 'محاولة غير قانونية',
            'national_id' => '999888777666',
            'family_members_count' => 3,
            'gender' => 'ذكر'
        ];

        $this->postJson('/api/beneficiaries', $data)->assertStatus(403);

        // محاولة حذف
        $beneficiary = Beneficiary::factory()->create();
        $this->deleteJson("/api/beneficiaries/{$beneficiary->id}")->assertStatus(403);
    }

    public function test_super_admin_can_delete_beneficiary()
    {
        Sanctum::actingAs($this->superAdmin);
        $beneficiary = Beneficiary::factory()->create();

        $response = $this->deleteJson("/api/beneficiaries/{$beneficiary->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('beneficiaries', ['id' => $beneficiary->id]);
    }
}
