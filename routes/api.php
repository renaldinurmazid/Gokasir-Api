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
    TableController,
    PublicOrderController,
    TableOrderController,
    SalesActivationController,
};

// ─── PUBLIC ──────────────────────────────────────────────────────────
Route::get('business-types',   [AuthController::class, 'businessTypes']);
Route::post('webhooks/ipaymu', [WebhookController::class, 'ipaymu']);
Route::post('webhooks/ipaymu-order', [WebhookController::class, 'ipaymuOrder']);

Route::prefix('public')->group(function () {
    Route::get('menu/{tableCode}',                   [PublicOrderController::class, 'menu']);
    Route::post('order/{tableCode}/session',          [PublicOrderController::class, 'startSession']);
    Route::post('order/{tableCode}/place',            [PublicOrderController::class, 'placeOrder']);
    Route::get('order/{tableCode}/status/{orderNumber}', [PublicOrderController::class, 'orderStatus']);
    Route::get('order/{tableCode}/history',           [PublicOrderController::class, 'orderHistory']);
    Route::get('payment-methods',                    [PublicOrderController::class, 'paymentMethods']);
    Route::get('activation-packages',                [TokenPricingController::class, 'publicPackages']);
});

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

    // Auth (Does not require activation)
    Route::prefix('auth')->group(function () {
        Route::post('logout',          [AuthController::class, 'logout']);
        Route::get('me',               [AuthController::class, 'me']);
        Route::put('profile',          [AuthController::class, 'updateProfile']);
        Route::put('change-password',  [AuthController::class, 'changePassword']);
        Route::delete('delete-account', [AuthController::class, 'deleteAccount']);
    });

    // Token Pricing, Logs, Balance (Does not require activation)
    Route::get('token-pricing', [TokenPricingController::class, 'index']);
    Route::get('token-balance', [TokenTopupController::class, 'balance']);
    Route::get('token-logs',    [TokenLogController::class, 'index']);

    // Token Topups (Does not require activation)
    Route::get('token-topups',                       [TokenTopupController::class, 'index']);
    Route::post('token-topups',                      [TokenTopupController::class, 'store']);
    Route::get('token-topups/payment-channels',      [TokenTopupController::class, 'paymentChannels']);
    Route::get('payment-methods',                    [TokenTopupController::class, 'paymentChannels']);
    Route::get('token-topups/{orderNumber}/check',   [TokenTopupController::class, 'checkStatus']);

    // Sales Activation (Does not require activation)
    Route::get('sales-activation-packages', [SalesActivationController::class, 'index']);
    Route::post('sales-activation-packages', [SalesActivationController::class, 'updatePrice']);

    // Sales Wallet
    Route::get('sales/wallet', [\App\Http\Controllers\Api\Sales\SalesWalletController::class, 'index']);

    // ─── ACTIVATED ONLY ROUTES ───────────────────────────────────────
    Route::middleware('activated')->group(function () {
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
        Route::get('sales/{sale}', [SaleController::class, 'show']);

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

        // Dining Tables (Tables)
        Route::get('tables', [TableController::class, 'index']);
        Route::get('tables/{table}', [TableController::class, 'show']);
        Route::get('tables/{table}/orders', [TableController::class, 'activeOrders']);

        Route::middleware('owner')->group(function () {
            Route::post('tables', [TableController::class, 'store']);
            Route::put('tables/{table}', [TableController::class, 'update']);
            Route::delete('tables/{table}', [TableController::class, 'destroy']);
            Route::post('tables/{table}/regenerate-qr', [TableController::class, 'regenerateQr']);
        });

        // Table Orders
        Route::get('table-orders/pending', [TableOrderController::class, 'pending']);
        Route::apiResource('table-orders', TableOrderController::class)->only(['index', 'show', 'update']);
        Route::post('table-orders/{tableOrder}/confirm', [TableOrderController::class, 'confirm']);
        Route::post('table-orders/{tableOrder}/cancel', [TableOrderController::class, 'cancel']);
        Route::post('table-orders/{tableOrder}/process-payment', [TableOrderController::class, 'processPayment']);
        Route::post('table-orders/{tableOrder}/complete', [TableOrderController::class, 'complete']);

        // Admin Token Pricing (owner only)
        Route::middleware('owner')->prefix('admin')->group(function () {
            Route::post('token-pricing',                 [TokenPricingController::class, 'store']);
            Route::put('token-pricing/{tokenPricing}',    [TokenPricingController::class, 'update']);
            Route::delete('token-pricing/{tokenPricing}', [TokenPricingController::class, 'destroy']);
            Route::put('tenants/{tenant}/token-price',    [AdminTenantController::class, 'setMitraTokenPrice']);
        });
    });
});
