<?php

namespace App\Services;

use App\Models\Distribution;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Exception;
use App\Services\SmsService;

class DistributionService
{
    protected InventoryService $inventoryService;
    protected InstallmentService $installmentService;
    protected SmsService $smsService;

   public function __construct(
    InventoryService $inventoryService,
    InstallmentService $installmentService,
    SmsService $smsService
) {
    $this->inventoryService = $inventoryService;
    $this->installmentService = $installmentService;
    $this->smsService = $smsService;
}

    /**
     * تنفيذ عملية التوزيع بالكامل (خصم من عهدة الجهة وإنشاء أقساط إن وجدت)
     */
    public function distribute(array $data, string $userId): Distribution
    {
        return DB::transaction(function () use ($data, $userId) {

            // 1. التحقق من جهة التوزيع
            $user = User::findOrFail($userId);
            $distributionEntityId = $user->distribution_entity_id;

            if (!$distributionEntityId) {
                throw new Exception("هذا المستخدم غير مرتبط بجهة توزيع حالياً، لا يمكنه إتمام عملية الصرف.");
            }

            // 2. معالجة المرفقات
            $imagePath = isset($data['beneficiary_image']) && $data['beneficiary_image'] instanceof UploadedFile
                ? $this->uploadFile($data['beneficiary_image'], 'distributions/images') : null;

            $documentPath = isset($data['beneficiary_document']) && $data['beneficiary_document'] instanceof UploadedFile
                ? $this->uploadFile($data['beneficiary_document'], 'distributions/documents') : null;

            // 3. إنشاء سجل التوزيع
            $quantity = isset($data['quantity']) ? (int) $data['quantity'] : 1;

            $distribution = Distribution::create([
                'receipt_number'         => $this->generateReceiptNumber(),
                'distribution_entity_id' => $distributionEntityId,
                'user_id'                => $userId,
                'beneficiary_id'         => $data['beneficiary_id'],
                'sacrifice_type_id'      => $data['sacrifice_type_id'],
                'payment_method'         => $data['payment_method'],
                'actual_price'           => $data['actual_price'],
                'quantity'               => $quantity,
                'beneficiary_image'      => $imagePath,
                'beneficiary_document'   => $documentPath,
                'notes'                  => $data['notes'] ?? null,
            ]);

            // 4. خصم الأضحية من عهدة الجهة (ينشئ حركة Out)
            $this->inventoryService->removeStock(
                $distribution->sacrifice_type_id,
                $distribution->quantity,
                $distribution,
                $distribution->distribution_entity_id,
                null,
                $userId
            );

            // 5. معالجة الأقساط
            if ($data['payment_method'] === 'installments') {
                $monthsCount = $data['months_count'] ?? throw new Exception("يرجى تحديد عدد أشهر التقسيط.");
                $this->installmentService->createContract($distribution->id, $data['beneficiary_id'], $data['actual_price'], $monthsCount);
            }

            DB::afterCommit(function () use ($distribution, $userId) {
                try {
                    $this->sendDistributionSms($distribution, $userId);
                } catch (\Exception $e) {
                    // تسجيل الخطأ في ملفات النظام دون تعطل واجهة المستخدم
                    \Illuminate\Support\Facades\Log::error("فشل إطلاق رسالة الصرف للإيصال رقم {$distribution->receipt_number}: " . $e->getMessage());
                }
            });

            return $distribution;
        });
    }

    /**
     * تحديث بيانات التوزيع (مع التصفير المخزني والمحاسبي الآمن)
     */
    public function updateDistribution(Distribution $distribution, array $data, string $userId): Distribution
    {
        return DB::transaction(function () use ($distribution, $data, $userId) {

            $newQuantity = $data['quantity'] ?? $distribution->quantity;
            $newSacrificeTypeId = $data['sacrifice_type_id'] ?? $distribution->sacrifice_type_id;
            $newActualPrice = $data['actual_price'] ?? $distribution->actual_price;
            $newPaymentMethod = $data['payment_method'] ?? $distribution->payment_method;

            // التحقق مما إذا كان التعديل يمس الحقول الجوهرية (المخزون أو المال)
            $coreChanged = (
                $newQuantity != $distribution->quantity ||
                $newSacrificeTypeId != $distribution->sacrifice_type_id ||
                $newActualPrice != $distribution->actual_price ||
                $newPaymentMethod != $distribution->payment_method
            );

            if ($coreChanged) {
                // 1. مسح العقد القديم (ستفشل العملية تلقائياً إذا كان هناك دفعات مسددة بفضل الحماية التي أضفناها)
                $this->installmentService->reverseContractForDistribution($distribution->id);

                // 2. عكس تأثير حركة المخزون القديمة (إرجاع الرصيد لجهة التوزيع)
                $this->inventoryService->reverseDocumentMovements($distribution);

                // 3. تحديث بيانات التوزيع
                $distribution->update([
                    'quantity'          => $newQuantity,
                    'sacrifice_type_id' => $newSacrificeTypeId,
                    'actual_price'      => $newActualPrice,
                    'payment_method'    => $newPaymentMethod,
                    'notes'             => $data['notes'] ?? $distribution->notes,
                ]);

                // 4. خصم الكمية الجديدة من المخزون
                $this->inventoryService->removeStock(
                    $distribution->sacrifice_type_id,
                    $distribution->quantity,
                    $distribution,
                    $distribution->distribution_entity_id,
                    null,
                    $userId
                );

                // 5. إنشاء عقد تقسيط جديد إذا كان الدفع الجديد بالأقساط
                if ($newPaymentMethod === 'installments') {
                    $monthsCount = $data['months_count'] ?? throw new Exception("يرجى تحديد عدد أشهر التقسيط.");
                    $this->installmentService->createContract($distribution->id, $distribution->beneficiary_id, $distribution->actual_price, $monthsCount);
                }
            } else {
                // إذا كان التعديل في الملاحظات فقط
                $distribution->update(['notes' => $data['notes'] ?? $distribution->notes]);
            }

            return $distribution;
        });
    }

    /**
     * حذف عملية التوزيع كلياً (عكس المخزون والعقود)
     */
    public function deleteDistribution(Distribution $distribution): void
    {
        DB::transaction(function () use ($distribution) {
            // 1. مسح العقد (يمنع الحذف إذا وُجدت مبالغ مدفوعة)
            $this->installmentService->reverseContractForDistribution($distribution->id);

            // 2. عكس التأثير المخزني (إرجاع الكمية لعهدة الجهة)
            $this->inventoryService->reverseDocumentMovements($distribution);

            // 3. حذف مستند التوزيع نفسه
            $distribution->delete();
        });
    }

    /**
     * تحديث المرفقات بشكل منفصل
     */
    public function updateAttachments(Distribution $distribution, ?UploadedFile $image, ?UploadedFile $document): void
    {
        if ($image) {
            if ($distribution->beneficiary_image) {
                Storage::disk('public')->delete($distribution->beneficiary_image);
            }
            $distribution->beneficiary_image = $this->uploadFile($image, 'distributions/images');
        }

        if ($document) {
            if ($distribution->beneficiary_document) {
                Storage::disk('public')->delete($distribution->beneficiary_document);
            }
            $distribution->beneficiary_document = $this->uploadFile($document, 'distributions/documents');
        }

        if ($image || $document) {
            $distribution->save();
        }
    }

    /**
     * جلب بيانات الإيصالات (مفردة أو جماعية) للإرسال للواجهة الأمامية
     */
    public function getReceipts(array $ids): Collection
    {
        return Distribution::with(['beneficiary', 'sacrificeType', 'user', 'installmentContract'])
            ->whereIn('id', $ids)
            ->get();
    }

    private function uploadFile(UploadedFile $file, string $path): string
    {
        $safeName = time() . '_' . $file->getClientOriginalName();
        return $file->storeAs($path, $safeName, 'public');
    }

    private function generateReceiptNumber(): string
    {
        $receiptNumber = date('YmdHis') . str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        while (Distribution::where('receipt_number', $receiptNumber)->exists()) {
            $receiptNumber = date('YmdHis') . str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        }
        return $receiptNumber;
    }



    protected function sendDistributionSms(Distribution $distribution, string $userId): void
  {
      // التأكد من تحميل العلاقات لجلب الاسم والنوع بأمان
      $distribution->loadMissing(['beneficiary', 'sacrificeType']);

      $beneficiary = $distribution->beneficiary;

      if ($beneficiary && !empty($beneficiary->phone)) {
          $content = "المستفيد المحترم/ {$beneficiary->name}، " .
                     "تم صرف عدد ({$distribution->quantity}) من {$distribution->sacrificeType->name} " .
                     "بموجب إيصال رقم: {$distribution->receipt_number}.";

          $this->smsService->sendIndividual($beneficiary, $content, $userId);
      }
  }
}
