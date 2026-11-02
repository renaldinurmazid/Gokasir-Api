<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RetentionController;
use App\Http\Controllers\Admin\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {

    Route::middleware('guest:admin')->group(function () {
        Route::get('login',  [AuthController::class, 'create'])->name('login');
        Route::post('login', [AuthController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [AuthController::class, 'destroy'])->name('logout');

        Route::get('/', DashboardController::class . '@index')->name('dashboard');

        Route::get('retention', [RetentionController::class, 'index'])->name('retention');

        Route::get('tenants',            [TenantController::class, 'index'])->name('tenants.index');
        Route::get('tenants/{tenant}',   [TenantController::class, 'show'])->withTrashed()->name('tenants.show');
        Route::post('tenants/{tenant}/suspend',     [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('tenants/{tenant}/activate',    [TenantController::class, 'activate'])->name('tenants.activate');
        Route::post('tenants/{tenant}/impersonate', [TenantController::class, 'impersonate'])->name('tenants.impersonate');
        Route::post('tenants/{tenant}/restore',     [TenantController::class, 'restore'])->withTrashed()->name('tenants.restore');
        Route::delete('tenants/{tenant}',           [TenantController::class, 'destroy'])->name('tenants.destroy');
    });
});
