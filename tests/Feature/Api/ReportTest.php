<?php

namespace Tests\Feature\Api;

use App\Models\Beneficiary;
use App\Models\FinancialAssistance;
use App\Models\InKindAssistance;
use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;

class ReportTest extends ApiTestCase
{
    /**
     * 1. اختبار كشف حساب الخزينة (Treasury Statement)
     * التحقق من الأرصدة الافتتاحية والختامية
     */
    public function test_treasury_statement_calculates_balances_correctly()
    {
        $treasury = Treasury::factory()->create(['balance' => 0]);

        // حركة قبل الفترة (تدخل في الرصيد الافتتاحي)
        TreasuryTransaction::factory()->create([
            'treasury_id' => $treasury->id,
            'amount' => 1000,
            'transaction_type' => 'deposit',
            'transaction_date' => '2026-01-01'
        ]);

        // حركة خلال الفترة (سحب)
        TreasuryTransaction::factory()->create([
            'treasury_id' => $treasury->id,
            'amount' => 200,
            'transaction_type' => 'withdrawal',
            'transaction_date' => '2026-02-15'
        ]);

        $response = $this->getJson("/api/reports/treasury-statement?treasury_id={$treasury->id}&from_date=2026-02-01&to_date=2026-02-28");

        $response->assertStatus(200)
            ->assertJsonPath('info.opening_balance', 1000)
            ->assertJsonPath('info.total_out', 200)
            ->assertJsonPath('info.closing_balance', 800);
    }

    /**
     * 2. اختبار كشف الملف الشامل للمستفيد (Beneficiary Ledger)
     */
    public function test_beneficiary_statement_returns_full_history()
    {
        $beneficiary = Beneficiary::factory()->create();

        // إنشاء مساعدة مالية بـ 500
        FinancialAssistance::factory()->create([
            'beneficiary_id' => $beneficiary->id,
            'approved_amount' => 500
        ]);

        // إنشاء مساعدة عينية مع صنفين
        $inKind = InKindAssistance::factory()->create(['beneficiary_id' => $beneficiary->id]);
        $inKind->items()->create(['description' => 'سلة غذائية']);
        $inKind->items()->create(['description' => 'بطانية']);

        $response = $this->getJson("/api/reports/beneficiary-statement?beneficiary_id={$beneficiary->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.beneficiary_info.total_financial_received', 500)
            ->assertJsonCount(2, 'data.in_kind_history.0.items');
    }

    /**
     * 3. اختبار تقرير توزيع المساعدات العينية (In-Kind Distribution)
     */
    public function test_in_kind_distribution_report_counts_items_correctly()
    {
        $assistance = InKindAssistance::factory()->create(['request_date' => '2026-02-10']);

        // إضافة 3 أصناف (2 متشابهين و 1 مختلف)
        $assistance->items()->create(['description' => 'حقيبة مدرسية']);
        $assistance->items()->create(['description' => 'حقيبة مدرسية']);
        $assistance->items()->create(['description' => 'زي موحد']);

        $response = $this->getJson("/api/reports/in-kind-distribution?from_date=2026-02-01&to_date=2026-02-28");

        $response->assertStatus(200)
            ->assertJsonFragment(['description' => 'حقيبة مدرسية', 'total_distributed' => 2])
            ->assertJsonPath('info.total_items_pieces', 3);
    }

    /**
     * 4. اختبار ملخص أرصدة الخزائن اللحظي (Global Balances)
     */
    public function test_global_balances_report_sums_all_treasuries()
    {
        // تصفير أي خزائن سابقة وإنشاء خزائن جديدة للاختبار
        Treasury::query()->delete();
        Treasury::factory()->create(['balance' => 1500]);
        Treasury::factory()->create(['balance' => 2500]);

        $response = $this->getJson("/api/reports/global-balances");

        $response->assertStatus(200)
            ->assertJsonPath('info.total_funds_available', 4000)
            ->assertJsonCount(2, 'data');
    }

    /**
     * 5. اختبار تحليل المساعدات المالية حسب النوع (Financial Aid by Type)
     */
    public function test_financial_aid_by_type_calculates_percentages_correctly()
    {
        // مساعدة اجتماعية (25%) بـ 250
        FinancialAssistance::factory()->create([
            'type' => 'social',
            'approved_amount' => 250,
            'request_date' => '2026-02-01'
        ]);
        // مساعدة علاجية (75%) بـ 750
        FinancialAssistance::factory()->create([
            'type' => 'medical',
            'approved_amount' => 750,
            'request_date' => '2026-02-05'
        ]);

        $response = $this->getJson("/api/reports/financial-aid-by-type?from_date=2026-02-01&to_date=2026-02-28");

        $response->assertStatus(200)
            ->assertJsonFragment(['type_key' => 'social', 'percentage' => 25])
            ->assertJsonFragment(['type_key' => 'medical', 'percentage' => 75]);
    }

    /**
     * 6. اختبار حماية التقارير (Authorization)
     */
    public function test_unauthorized_user_cannot_access_reports()
    {
        // مستخدم "مدخل بيانات" لا يملك صلاحية رؤية التقارير المالية (حسب الـ Seeder)
        Sanctum::actingAs($this->dataEntryUser);

        $response = $this->getJson("/api/reports/global-balances");

        $response->assertStatus(403);
    }
}
