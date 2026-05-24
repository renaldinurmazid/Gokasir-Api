<?php

use Illuminate\Support\Facades\Route;
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
};

// ─── PUBLIC ──────────────────────────────────────────────────────────
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

});
