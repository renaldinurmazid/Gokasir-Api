<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\TenantController as AdminTenantController;
use App\Http\Controllers\Api\{
    AuthController,
    StoreController,
    UserController,
    CashierController,
    CategoryController,
    UnitController,
    ProductController,
    StockController,
    StockMovementController,
    CustomerController,
    SaleController,
    ReceivableController,
    ExpenseController,
    ReportController,
    TenantController,
    TaxSettingController,
    TokenPricingController,
    TokenTopupController,
    TokenLogController,
    WebhookController,
    SupplierController,
    PurchaseController,
    PurchaseReturnController,
    PayableController,
};

// ─── PUBLIC ──────────────────────────────────────────────────────────
Route::get('business-types',   [AuthController::class, 'businessTypes']);
Route::post('webhooks/ipaymu', [WebhookController::class, 'ipaymu']);

Route::prefix('auth')->group(function () {
    Route::post('login',           [AuthController::class, 'login']);
    Route::post('register',        [AuthController::class, 'register']);
    Route::post('verify-otp',      [AuthController::class, 'verifyOtp']);
    Route::post('resend-otp',      [AuthController::class, 'resendOtp']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password',  [AuthController::class, 'resetPassword']);
});

// ─── AUTHENTICATED ───────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('logout',          [AuthController::class, 'logout']);
        Route::get('me',               [AuthController::class, 'me']);
        Route::put('profile',          [AuthController::class, 'updateProfile']);
        Route::put('change-password',  [AuthController::class, 'changePassword']);
        Route::delete('delete-account', [AuthController::class, 'deleteAccount']);
    });

    // Tenant Profile
    Route::get('tenant', [TenantController::class, 'show']);
    Route::middleware('owner')->put('tenant', [TenantController::class, 'update']);

    // Toko (owner only untuk CUD)
    Route::get('stores', [StoreController::class, 'index']);
    Route::get('stores/{store}', [StoreController::class, 'show']);
    Route::middleware('owner')->group(function () {
        Route::post('stores',           [StoreController::class, 'store']);
        Route::put('stores/{store}',    [StoreController::class, 'update']);
        Route::delete('stores/{store}', [StoreController::class, 'destroy']);
    });

    // Users (owner only)
    Route::middleware('owner')->group(function () {
        Route::get('users',           [UserController::class, 'index']);
        Route::post('users',          [UserController::class, 'store']);
        Route::put('users/{user}',    [UserController::class, 'update']);
        Route::delete('users/{user}', [UserController::class, 'destroy']);

        // Dedicated Cashier API
        Route::apiResource('cashiers', CashierController::class);
    });

    // Kategori & Satuan Produk
    Route::apiResource('categories', CategoryController::class)->except(['show']);
    Route::apiResource('units', UnitController::class)->except(['show']);

    // Produk
    Route::get('products/low-stock', [ProductController::class, 'lowStock']);
    Route::get('products/search-by-code', [ProductController::class, 'searchByBarcodeOrSku']);
    Route::apiResource('products', ProductController::class);

    // Stok
    Route::get('stocks', [StockController::class, 'index']);

    // Mutasi Stok
    Route::get('stock-movements',  [StockMovementController::class, 'index']);
    Route::post('stock-movements', [StockMovementController::class, 'store']);

    // Pelanggan
    Route::apiResource('customers', CustomerController::class);

    // Transaksi Penjualan
    Route::get('sales/today-overview', [SaleController::class, 'todayOverview']);
    Route::get('sales',       [SaleController::class, 'index']);
    Route::post('sales',      [SaleController::class, 'store']);
    Route::get('sales/{sale}',[SaleController::class, 'show']);

    // Piutang
    Route::get('receivables',                   [ReceivableController::class, 'index']);
    Route::get('receivables/{receivable}',      [ReceivableController::class, 'show']);
    Route::post('receivables/{receivable}/pay', [ReceivableController::class, 'pay']);
    Route::post('receivables/overdue-check',    [ReceivableController::class, 'markOverdue']);

    // ── Supplier ─────────────────────────────────────────────────────
    Route::prefix('suppliers')->group(function () {
        Route::get('/',                              [SupplierController::class, 'index']);
        Route::post('/',                             [SupplierController::class, 'store']);
        Route::get('/{supplier}',                    [SupplierController::class, 'show']);
        Route::put('/{supplier}',                    [SupplierController::class, 'update']);
        Route::delete('/{supplier}',                 [SupplierController::class, 'destroy']);
        Route::get('/{supplier}/history',            [SupplierController::class, 'history']);
        Route::post('/{supplier}/products',          [SupplierController::class, 'attachProduct']);
        Route::delete('/{supplier}/products/{productId}', [SupplierController::class, 'detachProduct']);
    });

    // ── Pembelian ─────────────────────────────────────────────────────
    Route::prefix('purchases')->group(function () {
        Route::get('/',             [PurchaseController::class, 'index']);
        Route::post('/',            [PurchaseController::class, 'store']);
        Route::get('/{purchase}',   [PurchaseController::class, 'show']);
    });

    // ── Retur Pembelian ───────────────────────────────────────────────
    Route::prefix('purchase-returns')->group(function () {
        Route::get('/',  [PurchaseReturnController::class, 'index']);
        Route::post('/', [PurchaseReturnController::class, 'store']);
    });

    // ── Hutang ke Supplier (Payable) ──────────────────────────────────
    Route::prefix('payables')->group(function () {
        Route::get('/',                       [PayableController::class, 'index']);
        Route::get('/{payable}',              [PayableController::class, 'show']);
        Route::post('/{payable}/pay',         [PayableController::class, 'pay']);
        Route::post('/overdue-check',         [PayableController::class, 'markOverdue']);
    });

    // Pengeluaran
    Route::get('expense-categories',        [ExpenseController::class, 'categories']);
    Route::post('expense-categories',       [ExpenseController::class, 'storeCategory']);
    Route::get('expenses',                  [ExpenseController::class, 'index']);
    Route::post('expenses',                 [ExpenseController::class, 'store']);
    Route::put('expenses/{expense}',        [ExpenseController::class, 'update']);
    Route::delete('expenses/{expense}',     [ExpenseController::class, 'destroy']);

    // Laporan (owner only)
    Route::middleware('owner')->prefix('reports')->group(function () {
        Route::get('summary',              [ReportController::class, 'summary']);
        Route::get('sales-by-day',         [ReportController::class, 'salesByDay']);
        Route::get('top-products',         [ReportController::class, 'topProducts']);
        Route::get('sales-by-payment',     [ReportController::class, 'salesByPaymentMethod']);
        Route::get('stock-value',          [ReportController::class, 'stockValue']);
        Route::get('cashier-performance',  [ReportController::class, 'cashierPerformance']);
        Route::get('expense-by-category',  [ReportController::class, 'expenseByCategory']);
    });

    // Tax Settings
    Route::get('tax-settings', [TaxSettingController::class, 'show']);
    Route::middleware('owner')->put('tax-settings', [TaxSettingController::class, 'update']);

    // Token Pricing, Logs, Balance
    Route::get('token-pricing', [TokenPricingController::class, 'index']);
    Route::get('token-balance', [TokenTopupController::class, 'balance']);
    Route::get('token-logs',    [TokenLogController::class, 'index']);

    // Token Topups
    Route::get('token-topups',                       [TokenTopupController::class, 'index']);
    Route::post('token-topups',                      [TokenTopupController::class, 'store']);
    Route::get('token-topups/payment-channels',      [TokenTopupController::class, 'paymentChannels']);
    Route::get('payment-methods',                    [TokenTopupController::class, 'paymentChannels']);
    Route::get('token-topups/{orderNumber}/check',   [TokenTopupController::class, 'checkStatus']);

    // Admin Token Pricing (owner only)
    Route::middleware('owner')->prefix('admin')->group(function () {
        Route::post('token-pricing',                 [TokenPricingController::class, 'store']);
        Route::put('token-pricing/{tokenPricing}',    [TokenPricingController::class, 'update']);
        Route::delete('token-pricing/{tokenPricing}', [TokenPricingController::class, 'destroy']);
        Route::put('tenants/{tenant}/token-price',    [AdminTenantController::class, 'setMitraTokenPrice']);
    });

});
