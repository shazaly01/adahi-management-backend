<?php

namespace Tests\Feature\Api;

use App\Models\Beneficiary;
use App\Models\FinancialAssistance;
use Tests\ApiTestCase;

class RichDashboardTest extends ApiTestCase
{
    public function test_rich_dashboard_returns_all_required_sections()
    {
        // إنشاء بيانات متنوعة
        Beneficiary::factory()->create(['gender' => 'ذكر', 'marital_status' => 'متزوج']);
        Beneficiary::factory()->create(['gender' => 'أنثى', 'marital_status' => 'أرمل']);

        FinancialAssistance::factory()->create(['approved_amount' => 1000, 'request_date' => now()]);

        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'cards' => ['total_beneficiaries', 'current_treasury_balance'],
                    'charts' => ['monthly_aid', 'demographics'],
                    'activities'
                ]
            ])
            ->assertJsonPath('data.charts.demographics.gender.male', 1);
    }
}
