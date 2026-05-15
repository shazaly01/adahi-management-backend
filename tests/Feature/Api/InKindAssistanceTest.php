<?php

namespace Tests\Feature\Api;

use App\Models\Beneficiary;
use App\Models\InKindAssistance;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;

class InKindAssistanceTest extends ApiTestCase
{
    /** --- 1. اختبار عرض المساعدات --- **/

    public function test_authorized_users_can_list_in_kind_assistances()
    {
        InKindAssistance::factory(2)->create();

        $response = $this->getJson('/api/in-kind-assistances');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data']);
    }

    /** --- 2. اختبار الإنشاء الناجح (طلب + أصناف) --- **/

    public function test_can_create_in_kind_assistance_with_multiple_items()
    {
        $beneficiary = Beneficiary::factory()->create();

        $data = [
            'beneficiary_id' => $beneficiary->id,
            'request_date' => now()->format('Y-m-d'),
            'reasons' => 'حاجة ماسة لمواد تنظيف',
            'items' => [
                ['description' => 'سجادة صلاة'],
                ['description' => 'منظفات منزلية متنوعة'],
                ['description' => 'أغطية شتوية']
            ]
        ];

        $response = $this->postJson('/api/in-kind-assistances', $data);

        $response->assertStatus(201);

        // التأكد من تخزين المساعدة
        $this->assertDatabaseHas('in_kind_assistances', [
            'beneficiary_id' => $beneficiary->id,
            'reasons' => 'حاجة ماسة لمواد تنظيف'
        ]);

        // التأكد من تخزين الأصناف الثلاثة
        $this->assertDatabaseCount('in_kind_assistance_items', 3);
        $this->assertDatabaseHas('in_kind_assistance_items', [
            'description' => 'سجادة صلاة'
        ]);
    }

    /** --- 3. اختبارات التحقق (Validation) --- **/

    public function test_create_in_kind_assistance_fails_if_items_are_missing()
    {
        $beneficiary = Beneficiary::factory()->create();

        $data = [
            'beneficiary_id' => $beneficiary->id,
            'request_date' => now()->format('Y-m-d'),
            'items' => [] // مصفوفة فارغة
        ];

        $response = $this->postJson('/api/in-kind-assistances', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    /** --- 4. اختبار الحذف والصلاحيات --- **/

    public function test_auditor_cannot_delete_in_kind_assistance()
    {
        Sanctum::actingAs($this->auditorUser);
        $assistance = InKindAssistance::factory()->create();

        $response = $this->deleteJson("/api/in-kind-assistances/{$assistance->id}");

        $response->assertStatus(403);
    }

    public function test_super_admin_can_delete_in_kind_assistance()
    {
        $assistance = InKindAssistance::factory()->create();

        $response = $this->deleteJson("/api/in-kind-assistances/{$assistance->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('in_kind_assistances', ['id' => $assistance->id]);
    }
}
