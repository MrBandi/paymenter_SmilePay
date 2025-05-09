<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Paymenter\Extensions\Gateways\SmilePay\SmilePay;

// 公開路由
Route::post('/extensions/smilepay/webhook', [SmilePay::class, 'webhook'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('extensions.gateways.smilepay.webhook');

// 付款創建路由 - 特別處理
Route::get('/extensions/smilepay/create-payment/{invoiceId}/{paymentMethod}', [SmilePay::class, 'directCreatePayment'])
    ->name('extensions.gateways.smilepay.create-payment');

// 管理員路由 - 修正中間件定義
Route::prefix('admin')->middleware(['web', 'auth', 'admin.auth'])->group(function () {
    Route::get('/extensions/smilepay/debug-logs/{invoiceId?}', [SmilePay::class, 'showDebugLogs'])
        ->name('admin.extensions.smilepay.debug-logs');
    
    Route::post('/extensions/smilepay/clear-logs/{invoiceId?}', [SmilePay::class, 'clearDebugLogs'])
        ->name('admin.extensions.smilepay.clear-logs');
});
