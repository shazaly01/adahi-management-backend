<?php

namespace App\Services;

use App\Models\InstallmentContract;
use App\Models\InstallmentPayment;
use App\Models\InstallmentSchedule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class InstallmentService
{
    /**
     * إنشاء عقد تقسيط جديد مع جدولة الدفعات شهرياً بشكل آمن
     */
    public function createContract(int $distributionId, int $beneficiaryId, int $totalAmount, int $monthsCount): InstallmentContract
    {
        return DB::transaction(function () use ($distributionId, $beneficiaryId, $totalAmount, $monthsCount) {

            // 1. إنشاء العقد الأساسي
            $contract = InstallmentContract::create([
                'distribution_id' => $distributionId,
                'beneficiary_id'  => $beneficiaryId,
                'total_amount'    => $totalAmount,
                'paid_amount'     => 0,
                'status'          => 'active',
            ]);

            // 2. حساب قيمة القسط الشهري ومعالجة الكسور
            $monthlyAmount = intdiv($totalAmount, $monthsCount);
            $remainder = $totalAmount % $monthsCount;

            $currentDate = Carbon::now();

            // 3. توليد جدول الأقساط
            for ($i = 1; $i <= $monthsCount; $i++) {
                // إضافة المتبقي (الكسور) للقسط الأخير لضمان تطابق الإجمالي
                $amountForThisMonth = ($i === $monthsCount) ? ($monthlyAmount + $remainder) : $monthlyAmount;

                InstallmentSchedule::create([
                    'installment_contract_id' => $contract->id,
                    'amount'                  => $amountForThisMonth,
                    'due_date'                => $currentDate->copy()->addMonths($i)->format('Y-m-d'),
                    'is_paid'                 => false,
                ]);
            }

            return $contract;
        });
    }

    /**
     * تسجيل دفعة قسط جديدة وتوزيعها على الأقساط المجدولة
     */
    public function collectPayment(array $data, int $collectorId): InstallmentPayment
    {
        return DB::transaction(function () use ($data, $collectorId) {
            // 1. جلب العقد مع قفل السجل لتجنب التزامن (Pessimistic Locking)
            $contract = InstallmentContract::where('id', $data['installment_contract_id'])
                                            ->lockForUpdate()
                                            ->firstOrFail();

            // 2. التحقق من أن العقد لم يكتمل بعد
            if ($contract->status === 'completed') {
                throw new Exception("هذا العقد مكتمل السداد بالفعل.");
            }

            // 3. التحقق من أن مبلغ الدفعة لا يتجاوز المبلغ المتبقي
            $remainingContractAmount = $contract->total_amount - $contract->paid_amount;
            if ($data['amount'] > $remainingContractAmount) {
                throw new Exception("مبلغ الدفعة ({$data['amount']}) يتجاوز المبلغ المتبقي المطلوب سداده ({$remainingContractAmount}).");
            }

            // 4. توليد رقم إيصال الدفع الفريد
            $receiptNumber = $this->generateReceiptNumber();

            // 5. تسجيل إيصال الدفعة
            $payment = InstallmentPayment::create([
                'installment_contract_id' => $contract->id,
                'receipt_number'          => $receiptNumber,
                'amount'                  => $data['amount'],
                'collected_by'            => $collectorId,
            ]);

            // 6. تحديث المبلغ المدفوع في العقد
            $contract->paid_amount += $data['amount'];

            // إغلاق العقد (تحديث الحالة) إذا تم سداد كامل المبلغ
            if ($contract->paid_amount >= $contract->total_amount) {
                $contract->status = 'completed';
            }
            $contract->save();

            // 7. خوارزمية تسديد الأقساط المجدولة (FIFO) - الأقدم استحقاقاً أولاً
            $this->allocatePaymentToSchedules($contract->id, $payment->id, $data['amount']);

            return $payment;
        });
    }

    /**
     * إلغاء ومسح عقد التقسيط بالكامل (يُستخدم عند تعديل أو حذف عملية التوزيع)
     * يحتوي على حماية محاسبية صارمة تمنع مسح عقد به دفعات
     */
    public function reverseContractForDistribution(int $distributionId): void
    {
        $contract = InstallmentContract::where('distribution_id', $distributionId)->lockForUpdate()->first();

        if ($contract) {
            if ($contract->paid_amount > 0) {
                throw new Exception("حماية محاسبية: لا يمكن تعديل أو حذف هذه التوزيعة لوجود عقد تقسيط تم سداد دفعات منه. يرجى إلغاء الإيصالات المالية المرتبطة أولاً.");
            }

            // حذف الجدولة والعقد (Soft Delete أو Hard Delete حسب إعداداتك، الافتراضي مسح لتنظيف الداتا)
            InstallmentSchedule::where('installment_contract_id', $contract->id)->delete();
            $contract->delete();
        }
    }

    /**
     * توزيع المبلغ المدفوع على جدول الأقساط ومعالجة السداد الجزئي (Row-Splitting)
     */
    private function allocatePaymentToSchedules(int $contractId, int $paymentId, int $amountToAllocate): void
    {
        // جلب الأقساط غير المدفوعة مرتبة حسب تاريخ الاستحقاق (الأقدم أولاً) مع القفل
        $unpaidSchedules = InstallmentSchedule::where('installment_contract_id', $contractId)
                                              ->where('is_paid', false)
                                              ->orderBy('due_date', 'asc')
                                              ->orderBy('id', 'asc')
                                              ->lockForUpdate()
                                              ->get();

        foreach ($unpaidSchedules as $schedule) {
            if ($amountToAllocate <= 0) {
                break; // تم توزيع المبلغ بالكامل
            }

            if ($amountToAllocate >= $schedule->amount) {
                // تسديد القسط بالكامل
                $schedule->update([
                    'is_paid' => true,
                    'installment_payment_id' => $paymentId,
                ]);

                $amountToAllocate -= $schedule->amount;
            } else {
                // المستفيد دفع مبلغاً جزئياً لا يغطي كامل القسط الحالي
                // 1. إنشاء قسط جديد بالمبلغ المتبقي بنفس تاريخ الاستحقاق
                InstallmentSchedule::create([
                    'installment_contract_id' => $schedule->installment_contract_id,
                    'amount'                  => $schedule->amount - $amountToAllocate,
                    'due_date'                => $schedule->due_date,
                    'is_paid'                 => false,
                ]);

                // 2. تحديث القسط الحالي ليكون مدفوعاً بقيمة المبلغ الجزئي
                $schedule->update([
                    'amount'                 => $amountToAllocate,
                    'is_paid'                => true,
                    'installment_payment_id' => $paymentId,
                ]);

                $amountToAllocate = 0; // تم استنفاد المبلغ
            }
        }
    }

    /**
     * توليد رقم إيصال فريد من 18 رقم
     */
    private function generateReceiptNumber(): string
    {
        $prefix = date('YmdHis'); // 14 خانة
        $random = str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT); // 4 خانات

        $receiptNumber = $prefix . $random; // الإجمالي 18 خانة

        // التحقق من عدم التكرار
        while (InstallmentPayment::where('receipt_number', $receiptNumber)->exists()) {
            $random = str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
            $receiptNumber = $prefix . $random;
        }

        return $receiptNumber;
    }
}
