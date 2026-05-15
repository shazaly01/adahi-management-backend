<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Treasury;
use App\Models\Beneficiary;
use App\Models\TreasuryTransaction;
use App\Models\InKindAssistanceItem;
use App\Models\InKindAssistance; // أضف هذا السطر
use App\Models\FinancialAssistance;

use App\Http\Requests\Report\TreasuryStatementRequest;
use App\Http\Requests\Report\BeneficiaryStatementRequest;
use App\Http\Requests\Report\PeriodReportRequest;
use App\Http\Resources\Api\TreasuryStatementResource;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function treasuryStatement(TreasuryStatementRequest $request): JsonResponse
    {
        // فحص الصلاحية: يجب أن يملك المستخدم صلاحية عرض الخزينة
        $this->authorize('viewAny', Treasury::class);

        $treasuryId = $request->treasury_id;
        $from = $request->from_date;
        $to = $request->to_date;

        $treasury = Treasury::findOrFail($treasuryId);

        $previousIn = TreasuryTransaction::where('treasury_id', $treasuryId)
            ->where('transaction_date', '<', $from)
            ->where('transaction_type', 'deposit')
            ->sum('amount');

        $previousOut = TreasuryTransaction::where('treasury_id', $treasuryId)
            ->where('transaction_date', '<', $from)
            ->where('transaction_type', 'withdrawal')
            ->sum('amount');

        $openingBalance = $previousIn - $previousOut;

        $transactions = TreasuryTransaction::with('user')
            ->where('treasury_id', $treasuryId)
            ->whereBetween('transaction_date', [$from, $to])
            ->orderBy('transaction_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $periodIn = $transactions->where('transaction_type', 'deposit')->sum('amount');
        $periodOut = $transactions->where('transaction_type', 'withdrawal')->sum('amount');

        return response()->json([
            'status' => true,
            'info' => [
                'treasury_name'   => $treasury->name,
                'period'          => ['from' => $from, 'to' => $to],
                'opening_balance' => (float) $openingBalance,
                'total_in'        => (float) $periodIn,
                'total_out'       => (float) $periodOut,
                'closing_balance' => (float) ($openingBalance + $periodIn - $periodOut),
            ],
            'transactions' => TreasuryStatementResource::collection($transactions)
        ]);
    }

    public function beneficiaryStatement(BeneficiaryStatementRequest $request): JsonResponse
    {
        // فحص الصلاحية: يجب أن يملك المستخدم صلاحية عرض المستفيدين
        $this->authorize('viewAny', Beneficiary::class);

        $beneficiary = Beneficiary::with([
            'financialAssistances',
            'inKindAssistances.items'
        ])->findOrFail($request->beneficiary_id);

        $totalFinancial = $beneficiary->financialAssistances->sum('approved_amount');
        $totalInKindCount = $beneficiary->inKindAssistances->count();

        return response()->json([
            'status' => true,
            'data' => [
                'beneficiary_info' => [
                    'name' => $beneficiary->name,
                    'national_id' => $beneficiary->national_id,
                    'phone' => $beneficiary->phone,
                    'total_financial_received' => (float) $totalFinancial,
                    'total_in_kind_requests' => $totalInKindCount,
                ],
                'financial_history' => $beneficiary->financialAssistances->map(function ($aid) {
                    return [
                        'date' => $aid->request_date,
                        'type' => $aid->type == 'social' ? 'اجتماعية' : 'علاجية',
                        'amount' => (float) $aid->approved_amount,
                    ];
                }),
                'in_kind_history' => $beneficiary->inKindAssistances->map(function ($aid) {
                    return [
                        'date' => $aid->request_date,
                        'reasons' => $aid->reasons,
                        'items' => $aid->items->pluck('description'),
                    ];
                }),
            ]
        ]);
    }

    public function inKindDistributionReport(PeriodReportRequest $request): JsonResponse
    {
        // فحص الصلاحية: هل مسموح له برؤية المساعدات العينية؟
        $this->authorize('viewAny', InKindAssistance::class);

        $from = $request->from_date;
        $to = $request->to_date;

        $distribution = InKindAssistanceItem::query()
            ->whereHas('assistance', function ($query) use ($from, $to) {
                $query->whereBetween('request_date', [$from, $to]);
            })
            ->select('description', DB::raw('count(*) as total_distributed'))
            ->groupBy('description')
            ->orderBy('total_distributed', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'info' => [
                'report_name' => 'تقرير توزيع المساعدات العينية',
                'period'      => ['from' => $from, 'to' => $to],
                'unique_items_types' => $distribution->count(),
                'total_items_pieces' => $distribution->sum('total_distributed'),
            ],
            'data' => $distribution
        ]);
    }

    public function globalBalances(): JsonResponse
    {
        // فحص الصلاحية: هل مسموح له برؤية أرصدة الخزائن؟
        $this->authorize('viewAny', Treasury::class);

        $treasuries = Treasury::select('id', 'name', 'balance')
            ->orderBy('balance', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'info' => [
                'report_name' => 'ملخص أرصدة الخزائن اللحظي',
                'generated_at' => now()->toDateTimeString(),
                'total_funds_available' => (float) $treasuries->sum('balance'),
                'treasuries_count' => $treasuries->count(),
            ],
            'data' => $treasuries->map(function ($treasury) {
                return [
                    'id' => $treasury->id,
                    'name' => $treasury->name,
                    'balance' => (float) $treasury->balance,
                ];
            })
        ]);
    }

    public function financialAidByType(PeriodReportRequest $request): JsonResponse
    {
        // فحص الصلاحية: هل مسموح له برؤية المساعدات المالية؟
        $this->authorize('viewAny', FinancialAssistance::class);

        $from = $request->from_date;
        $to = $request->to_date;

        $stats = FinancialAssistance::query()
            ->whereBetween('request_date', [$from, $to])
            ->select(
                'type',
                DB::raw('SUM(approved_amount) as total_amount'),
                DB::raw('COUNT(*) as cases_count')
            )
            ->groupBy('type')
            ->get();

        $grandTotal = $stats->sum('total_amount');

        $reportData = $stats->map(function ($item) use ($grandTotal) {
            return [
                'type_key'    => $item->type,
                'type_name'   => $item->type == 'social' ? 'مساعدات اجتماعية' : 'مساعدات علاجية',
                'amount'      => (float) $item->total_amount,
                'cases_count' => $item->cases_count,
                'percentage'  => $grandTotal > 0 ? round(($item->total_amount / $grandTotal) * 100, 2) : 0,
            ];
        });

        return response()->json([
            'status' => true,
            'info' => [
                'report_name' => 'تحليل المساعدات المالية حسب النوع',
                'period'      => ['from' => $from, 'to' => $to],
                'grand_total' => (float) $grandTotal,
                'total_cases' => $stats->sum('cases_count'),
            ],
            'data' => $reportData
        ]);
    }
}
