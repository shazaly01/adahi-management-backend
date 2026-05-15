<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

// استيراد النماذج (Models)
use App\Models\Beneficiary;
use App\Models\Distribution;
use App\Models\InstallmentContract;
use App\Models\InstallmentPayment;
use App\Models\InventoryMovement; // سنعتمد عليه كلياً في المخزون
use App\Models\SacrificeType;

class DashboardController extends Controller
{
    /**
     * جلب كافة بيانات وإحصائيات لوحة التحكم
     */
    public function index(Request $request): JsonResponse
    {
        // 1. الإحصائيات العامة
        $totalBeneficiaries = Beneficiary::count();
        $distributionsByType = Distribution::select('payment_method', DB::raw('count(*) as count'))
            ->groupBy('payment_method')
            ->pluck('count', 'payment_method');
        $totalDistributions = $distributionsByType->sum();

        // 2. الوضع المالي
        $cashDistributionsSum = Distribution::where('payment_method', 'cash')->sum('actual_price');
        $contractsTotal = InstallmentContract::sum('total_amount');
        $contractsPaid = InstallmentContract::sum('paid_amount');

        $totalExpected = $cashDistributionsSum + $contractsTotal;
        $totalCollected = $cashDistributionsSum + $contractsPaid;
        $totalDebts = $contractsTotal - $contractsPaid;

        // =============================================================
        // 3. حساب المخزون من "سجل الحركات" (Logic Update)
        // =============================================================

        // أ. رصيد المخزن الرئيسي: (إجمالي التوريدات - إجمالي ما تم تسليمه كعهدة)
        $mainStoreStock = SacrificeType::select('id', 'name')
            ->get()
            ->map(function ($type) {
                // مجموع ما دخل (Supplies)
                $in = InventoryMovement::where('sacrifice_type_id', $type->id)
                    ->where('movement_type', 'supply')
                    ->sum('quantity');

                // مجموع ما خرج كعهدة (Allocations)
                $out = InventoryMovement::where('sacrifice_type_id', $type->id)
                    ->where('movement_type', 'allocation')
                    ->sum('quantity');

                return [
                    'id' => $type->id,
                    'type_name' => $type->name,
                    'quantity' => (int) ($in - $out), // الرصيد الصافي في الحظيرة المركزية
                ];
            });

        // ب. إجمالي الأرصدة لدى جميع الجهات: (إجمالي العهد المستلمة - إجمالي ما تم توزيعه فعلياً)
        $totalAllocated = InventoryMovement::where('movement_type', 'allocation')->sum('quantity');
        $totalDistributed = InventoryMovement::where('movement_type', 'distribution')->sum('quantity');
        $entitiesTotalStock = $totalAllocated - $totalDistributed;

        // =============================================================

        // 4. أحدث الحركات
        $recentDistributions = Distribution::with(['beneficiary:id,name', 'sacrificeType:id,name'])
            ->latest()->take(5)->get()->map(fn($dist) => [
                'id' => $dist->id,
                'receipt_number' => $dist->receipt_number,
                'beneficiary_name' => $dist->beneficiary->name ?? 'غير معروف',
                'type_name' => $dist->sacrificeType->name ?? '',
                'payment_method' => $dist->payment_method,
                'created_at' => $dist->created_at->format('Y-m-d H:i'),
            ]);

        $recentPayments = InstallmentPayment::with(['contract.beneficiary:id,name'])
            ->latest()->take(5)->get()->map(fn($payment) => [
                'id' => $payment->id,
                'receipt_number' => $payment->receipt_number,
                'beneficiary_name' => $payment->contract->beneficiary->name ?? 'غير معروف',
                'amount' => $payment->amount,
                'created_at' => $payment->created_at->format('Y-m-d H:i'),
            ]);

        return response()->json([
            'general' => [
                'total_beneficiaries' => $totalBeneficiaries,
                'total_distributions' => $totalDistributions,
                'distributions_by_type' => [
                    'free' => $distributionsByType['free'] ?? 0,
                    'cash' => $distributionsByType['cash'] ?? 0,
                    'installments' => $distributionsByType['installments'] ?? 0,
                ]
            ],
            'financial' => [
                'expected' => (int) $totalExpected,
                'collected' => (int) $totalCollected,
                'debts' => (int) $totalDebts,
            ],
            'inventory' => [
                'main_store' => $mainStoreStock,
                'distributors_total' => (int) $entitiesTotalStock,
            ],
            'recent_activities' => [
                'distributions' => $recentDistributions,
                'payments' => $recentPayments,
            ]
        ]);
    }
}
