<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});
Route::get('/register', function () {
    return Inertia::render('Register');
});

Route::get('order/{tableCode}', [OrderController::class, 'index'])->name('web-order');
Route::get('order/{tableCode}/search', [OrderController::class, 'search'])->name('web-order.search');
Route::get('order/{tableCode}/checkout', [OrderController::class, 'checkout'])->name('web-order.checkout');
Route::get('order/{tableCode}/status/{orderNumber}', [OrderController::class, 'status'])->name('web-order.status');
Route::get('order/{tableCode}/history', [OrderController::class, 'history'])->name('web-order.history');
Route::get('order/{tableCode}/cancel/{orderNumber}', [OrderController::class, 'cancel'])->name('web-order.cancel');
Route::get('order/{tableCode}/{category}', [OrderController::class, 'showCategory'])->name('web-order.category');

Route::get('/profile/{tableCode}', function ($tableCode) {
    return view('pages.profile.index', ['tableCode' => $tableCode]);
});
Route::get('/profile/{tableCode}/privacy', function ($tableCode) {
    return view('pages.profile.privacy', ['tableCode' => $tableCode]);
});
