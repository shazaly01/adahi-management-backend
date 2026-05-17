<?php

namespace App\Services;

use App\Models\Distribution;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class DistributionService
{
    protected InventoryService $inventoryService;
    protected InstallmentService $installmentService;

    /**
     * حقن خدمات المخزون والأقساط
     */
    public function __construct(InventoryService $inventoryService, InstallmentService $installmentService)
    {
        $this->inventoryService = $inventoryService;
        $this->installmentService = $installmentService;
    }

    /**
     * تنفيذ عملية التوزيع بالكامل
     */
    public function distribute(array $data, string $userId): Distribution
    {
        return DB::transaction(function () use ($data, $userId) {

            // 1. التحقق من جهة التوزيع التابع لها المستخدم (المنفذ)
            $user = User::findOrFail($userId);
            $distributionEntityId = $user->distribution_entity_id;

            if (!$distributionEntityId) {
                throw new Exception("هذا المستخدم غير مرتبط بجهة توزيع حالياً، لا يمكنه إتمام عملية الصرف.");
            }

            $imagePath = null;
            $documentPath = null;

            // 2. معالجة المرفقات مع الالتزام بقاعدتك (الاسم الأصلي)
            if (isset($data['beneficiary_image']) && $data['beneficiary_image'] instanceof UploadedFile) {
                $imagePath = $this->uploadFile($data['beneficiary_image'], 'distributions/images');
            }

            if (isset($data['beneficiary_document']) && $data['beneficiary_document'] instanceof UploadedFile) {
                $documentPath = $this->uploadFile($data['beneficiary_document'], 'distributions/documents');
            }

            // 3. توليد رقم الإيصال (DECIMAL 18,0)
            $receiptNumber = $this->generateReceiptNumber();

            // 4. تحديد الكمية (الافتراض 1)
            $quantity = isset($data['quantity']) ? (int) $data['quantity'] : 1;

            // 5. إنشاء سجل التوزيع
            $distribution = Distribution::create([
                'receipt_number'         => $receiptNumber,
                'distribution_entity_id' => $distributionEntityId, // الجهة التي خرجت منها الأضحية
                'user_id'                => $userId,               // الموظف المنفذ
                'beneficiary_id'         => $data['beneficiary_id'],
                'sacrifice_type_id'      => $data['sacrifice_type_id'],
                'payment_method'         => $data['payment_method'],
                'actual_price'           => $data['actual_price'],
                'quantity'               => $quantity,             // حفظ الكمية في الداتا بيز
                'beneficiary_image'      => $imagePath,
                'beneficiary_document'   => $documentPath,
                'notes'                  => $data['notes'] ?? null,
            ]);

            // 6. خصم الأضحية من عهدة الجهة
            // الترتيب: sacrificeTypeId, quantity, reference, entityId, warehouseId, userId
            $this->inventoryService->removeStock(
                $data['sacrifice_type_id'],
                $quantity,
                $distribution,
                $distributionEntityId, // المصدر: جهة التوزيع
                null,                  // لا يوجد warehouse_id هنا لأن الخصم من عهدة الجهة
                $userId                // المنفذ
            );

            // 7. معالجة الأقساط
            if ($data['payment_method'] === 'installments') {
                $monthsCount = $data['months_count'] ?? throw new Exception("يرجى تحديد عدد أشهر التقسيط.");

                $this->installmentService->createContract(
                    $distribution->id,
                    $data['beneficiary_id'],
                    $data['actual_price'], // ملاحظة: السعر الفعلي يتم تمريره للـ Service الخاصة بالأقساط كإجمالي أو قسط بناءً على اللوجيك هناك
                    $monthsCount
                );
            }

            return $distribution;
        });
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

    /**
     * رفع الملفات مع الحفاظ على الاسم الأصلي للشفافية
     */
    private function uploadFile(UploadedFile $file, string $path): string
    {
        // نستخدم الطابع الزمني فقط لمنع التكرار مع بقاء الاسم الأصلي
        $safeName = time() . '_' . $file->getClientOriginalName();
        return $file->storeAs($path, $safeName, 'public');
    }

    /**
     * توليد رقم إيصال فريد 18 خانة
     */
    private function generateReceiptNumber(): string
    {
        $receiptNumber = date('YmdHis') . str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

        while (Distribution::where('receipt_number', $receiptNumber)->exists()) {
            $receiptNumber = date('YmdHis') . str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        }

        return $receiptNumber;
    }
}
