<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- استيراد الـ Controllers القديمة ---
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\MessageController;

// --- استيراد الـ Controllers الجديدة (نظام الأضاحي والمخزون) ---
use App\Http\Controllers\Api\BeneficiaryController;
use App\Http\Controllers\Api\SacrificeTypeController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\DistributionEntityController;
use App\Http\Controllers\Api\SupplyController;
use App\Http\Controllers\Api\AllocationController;
use App\Http\Controllers\Api\DistributionController;
use App\Http\Controllers\Api\DistributionDeliveryController;
use App\Http\Controllers\Api\InventoryMovementController;
use App\Http\Controllers\Api\EntityStockController;
use App\Http\Controllers\Api\InstallmentContractController;
use App\Http\Controllers\Api\InstallmentPaymentController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- المسارات العامة (Public Routes) ---
Route::post('/login', [AuthController::class, 'login']);
Route::post('distributions/receipts', [DistributionController::class, 'receipts'])->name('distributions.receipts');

// --- المسارات المحمية (Protected Routes) ---
Route::middleware('auth:sanctum')->group(function () {

    // 1. لوحة التحكم (الإحصائيات)
    Route::get('dashboard', [ReportController::class, 'dashboard']);

    // 2. إدارة النسخ الاحتياطي
    Route::prefix('backups')->name('backups.')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->middleware('can:backup.view');
        Route::post('/', [BackupController::class, 'store'])->middleware('can:backup.create');
        Route::get('/download', [BackupController::class, 'download'])->middleware('can:backup.download');
        Route::delete('/', [BackupController::class, 'destroy'])->middleware('can:backup.delete');
    });

    // 3. إدارة المستخدمين والأدوار
    Route::apiResource('users', UserController::class);
    Route::get('roles/permissions', [RoleController::class, 'getAllPermissions'])->name('roles.permissions');
    Route::apiResource('roles', RoleController::class);

    // 4. نظام المساعدات (المستفيدون)
    // الحل هنا: إضافة مسار check قبل مسار الـ Resource لتجنب الـ Shadowing
    Route::get('beneficiaries/check', [BeneficiaryController::class, 'check'])->name('beneficiaries.check');

    Route::get('beneficiaries/{beneficiary}/assistances', [BeneficiaryController::class, 'getAssistances'])
        ->name('beneficiaries.assistances');
    Route::apiResource('beneficiaries', BeneficiaryController::class);

    Route::apiResource('messages', MessageController::class)->only(['index', 'store', 'destroy']);

    // ---------------------------------------------------------------------
    // --- 9. نظام إدارة الأضاحي (الإضافات الجديدة) ---
    // ---------------------------------------------------------------------

    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('warehouses', WarehouseController::class);
    Route::apiResource('distribution-entities', DistributionEntityController::class);
    Route::apiResource('sacrifice-types', SacrificeTypeController::class);
    Route::apiResource('supplies', SupplyController::class)->except(['destroy']);

    // --- مسارات إيصالات التسليم للجهات والتوزيع للأفراد (يجب أن تسبق الـ apiResource) ---
    Route::get('allocations/{allocation}/receipt', [AllocationController::class, 'receipt'])->name('allocations.receipt');


    Route::get('distributions/deliveries/search', [DistributionDeliveryController::class, 'search'])->name('distributions.deliveries.search');
    Route::patch('distributions/deliveries/{distribution}/toggle', [DistributionDeliveryController::class, 'toggleStatus'])->name('distributions.deliveries.toggle');
    // --- مسارات الـ Resources الأساسية للجهات والأفراد ---
    Route::apiResource('allocations', AllocationController::class)->except(['destroy']);
    Route::apiResource('distributions', DistributionController::class)->except(['destroy']);

    // ---------------------------------------------------------------------
    // --- 10. نظام الأقساط والعقود ---
    // ---------------------------------------------------------------------

    Route::apiResource('installment-contracts', InstallmentContractController::class)->only(['index', 'show']);
    Route::apiResource('installment-payments', InstallmentPaymentController::class)->only(['index', 'store', 'show']);

    // ---------------------------------------------------------------------
    // --- 11. الدفاتر والأرصدة المخزنية (للقراءة فقط) ---
    // ---------------------------------------------------------------------

    // مسارات التقارير اللحظية (تمت إضافتها هنا)
    // مسارات التقارير الديناميكية الجديدة
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/inventory', [ReportController::class, 'inventoryReport'])->name('inventory');
        Route::get('/distributions', [ReportController::class, 'distributionsReport'])->name('distributions');
    });

    Route::apiResource('inventory-movements', InventoryMovementController::class)->only(['index', 'show']);
    Route::apiResource('entity-stocks', EntityStockController::class)->only(['index', 'show']);

    // 12. بيانات المستخدم الحالي وتسجيل الخروج
    Route::get('/user', function (Request $request) {
        $user = $request->user()->load('roles:id,name', 'roles.permissions:id,name');
        return response()->json($user);
    });
    Route::post('/logout', [AuthController::class, 'logout']);

});
