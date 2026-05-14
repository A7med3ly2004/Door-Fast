<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Delivery\AuthController;
use App\Http\Controllers\Api\Delivery\DashboardController;
use App\Http\Controllers\Api\Delivery\ShiftController;
use App\Http\Controllers\Api\Delivery\OrderController;
use App\Http\Controllers\Api\Delivery\WalletController;

// ── Public: Login ──────────────────────────────────────────────────────────
Route::post('/delivery/login', [AuthController::class, 'login'])->name('api.delivery.login');
Route::post('/reserve/login', [AuthController::class, 'login'])->name('api.reserve.login');

// ── Protected: Delivery Mobile API ────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:delivery'])
    ->prefix('delivery')
    ->name('api.delivery.')
    ->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/dashboard', [DashboardController::class, 'data']);
        Route::post('/shift/start', [ShiftController::class, 'start']);
        Route::post('/shift/end', [ShiftController::class, 'end']);
        Route::get('/shift/status', [ShiftController::class, 'status']);
        Route::get('/shift/times', [ShiftController::class, 'shiftTimes']);
        Route::get('/wallet/statement', [WalletController::class, 'statement']);

        Route::get('/orders/new', [OrderController::class, 'newOrders']);
        Route::post('/orders/{id}/accept', [OrderController::class, 'accept']);
        Route::get('/orders/received', [OrderController::class, 'received']);
        Route::post('/orders/{id}/deliver', [OrderController::class, 'deliver']);
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
        Route::get('/orders/delivered', [OrderController::class, 'delivered']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::get('/orders/{id}/invoice', [OrderController::class, 'downloadInvoice']);
    });

// ── Protected: Reserve Delivery Mobile API ────────────────────────────────
Route::middleware(['auth:sanctum', 'role:reserve_delivery'])
    ->prefix('reserve')
    ->name('api.reserve.')
    ->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/dashboard', [DashboardController::class, 'data']);
        Route::post('/shift/start', [ShiftController::class, 'start']);
        Route::post('/shift/end', [ShiftController::class, 'end']);
        Route::get('/shift/status', [ShiftController::class, 'status']);
        Route::get('/shift/times', [ShiftController::class, 'shiftTimes']);
        Route::get('/wallet/statement', [WalletController::class, 'statement']);

        Route::get('/orders/new', [OrderController::class, 'newOrders']);
        Route::post('/orders/{id}/accept', [OrderController::class, 'accept']);
        Route::get('/orders/received', [OrderController::class, 'received']);
        Route::post('/orders/{id}/deliver', [OrderController::class, 'deliver']);
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
        Route::get('/orders/delivered', [OrderController::class, 'delivered']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::get('/orders/{id}/invoice', [OrderController::class, 'downloadInvoice']);
    });
