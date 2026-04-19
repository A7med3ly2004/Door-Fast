<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\OrderController as AdminOrders;
use App\Http\Controllers\Admin\ClientController as AdminClients;
use App\Http\Controllers\Admin\ShopController as AdminShops;
use App\Http\Controllers\Admin\DeliveryManagementController as AdminDelivery;
use App\Http\Controllers\Admin\CallCenterManagementController as AdminCallCenter;
use App\Http\Controllers\Admin\ReportController as AdminReports;
use App\Http\Controllers\Admin\ReportHopsController as AdminReportHops;
use App\Http\Controllers\Admin\ReportDiscountController as AdminReportDiscounts;
use App\Http\Controllers\Admin\SettingController as AdminSettings;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\ActivityLogController as AdminActivityLog;
use App\Http\Controllers\Admin\TreasuryController;
use App\Http\Controllers\CallCenter\DashboardController as CCDashboard;
use App\Http\Controllers\CallCenter\OrderController as CCOrders;
use App\Http\Controllers\CallCenter\ClientController as CCClients;
use App\Http\Controllers\CallCenter\ShopController as CCShops;
use App\Http\Controllers\CallCenter\DeliveryViewController as CCDelivery;
use App\Http\Controllers\CallCenter\StatsController as CCStats;
use App\Http\Controllers\Delivery\DashboardController as DeliveryDashboard;

// ── Login ──
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::redirect('/', '/login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ── Admin ──
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');
        Route::get('/dashboard/stats', [AdminDashboard::class, 'stats'])->name('dashboard.stats');
        Route::get('/dashboard/recent-orders', [AdminDashboard::class, 'recentOrders'])->name('dashboard.recent-orders');
        Route::get('/dashboard/activity', [AdminDashboard::class, 'activity'])->name('dashboard.activity');

        // Admin Order Creation
        Route::get('/create-order/client-search', [AdminOrderController::class, 'searchClient'])->name('orders.client-search');
        Route::get('/create-order', [AdminOrderController::class, 'create'])->name('orders.create');
        Route::post('/create-order', [AdminOrderController::class, 'store'])->name('orders.store');

        // Orders
        Route::get('/orders/export-pdf', [AdminOrders::class, 'exportPdf'])->name('orders.export-pdf');
        Route::get('/orders', [AdminOrders::class, 'index'])->name('orders.index');
        Route::get('/orders/{id}', [AdminOrders::class, 'show'])->name('orders.show');
        Route::patch('/orders/{id}/cancel', [AdminOrders::class, 'cancel'])->name('orders.cancel');

        // Clients
        Route::get('/clients', [AdminClients::class, 'index'])->name('clients.index');
        Route::post('/clients', [AdminClients::class, 'store'])->name('clients.store');
        Route::get('/clients/{id}', [AdminClients::class, 'show'])->name('clients.show');
        Route::put('/clients/{id}', [AdminClients::class, 'update'])->name('clients.update');
        Route::delete('/clients/{id}', [AdminClients::class, 'destroy'])->name('clients.destroy');

        // Shops
        Route::get('/shops', [AdminShops::class, 'index'])->name('shops.index');
        Route::post('/shops', [AdminShops::class, 'store'])->name('shops.store');
        Route::get('/shops/{id}', [AdminShops::class, 'show'])->name('shops.show');
        Route::put('/shops/{id}', [AdminShops::class, 'update'])->name('shops.update');
        Route::patch('/shops/{id}/toggle', [AdminShops::class, 'toggle'])->name('shops.toggle');
        Route::post('/shop-categories', [AdminShops::class, 'storeCategory'])->name('shop-categories.store');

        // Delivery
        Route::get('/delivery', [AdminDelivery::class, 'index'])->name('delivery.index');
        Route::post('/delivery', [AdminDelivery::class, 'store'])->name('delivery.store');
        Route::put('/delivery/{id}', [AdminDelivery::class, 'update'])->name('delivery.update');
        Route::get('/delivery/{id}/performance', [AdminDelivery::class, 'performance'])->name('delivery.performance');
        Route::get('/delivery/{id}/settlement', [AdminOrderController::class, 'deliverySettlement'])->name('delivery.settlement');
        Route::post('/delivery/{id}/settlement', [AdminOrderController::class, 'doDeliverySettlement'])->name('delivery.do-settlement');

        // Call Center
        Route::get('/callcenter', [AdminCallCenter::class, 'index'])->name('callcenter.index');
        Route::post('/callcenter/{user}/settle', [\App\Http\Controllers\Admin\CallCenterManagementController::class, 'settle'])->name('callcenter.settle');
        Route::post('/callcenter', [AdminCallCenter::class, 'store'])->name('callcenter.store');
        Route::put('/callcenter/{id}', [AdminCallCenter::class, 'update'])->name('callcenter.update');
        Route::get('/callcenter/{id}/performance', [AdminCallCenter::class, 'performance'])->name('callcenter.performance');

        // Reports
        Route::get('/reports/data', [AdminReports::class, 'data'])->name('reports.data');
        Route::get('/reports/export-pdf', [AdminReports::class, 'exportPdf'])->name('reports.export-pdf');
        Route::get('/reports', [AdminReports::class, 'index'])->name('reports.index');

        // Report Hops
        Route::get('/report-hops/data', [AdminReportHops::class, 'data'])->name('report-hops.data');
        Route::get('/report-hops/{shopId}/pdf', [AdminReportHops::class, 'exportPdf'])->name('report-hops.pdf');
        Route::get('/report-hops', [AdminReportHops::class, 'index'])->name('report-hops.index');

        // Discount Reports
        Route::get('/report-discounts/clients', [AdminReportDiscounts::class, 'searchClients'])->name('report-discounts.clients');
        Route::get('/report-discounts/data', [AdminReportDiscounts::class, 'data'])->name('report-discounts.data');
        Route::get('/report-discounts/{id}/detail', [AdminReportDiscounts::class, 'orderDetail'])->name('report-discounts.detail');
        Route::get('/report-discounts', [AdminReportDiscounts::class, 'index'])->name('report-discounts.index');

        // Treasury (الخزنة)
        Route::get('/treasury', [App\Http\Controllers\Admin\TreasuryController::class, 'index'])->name('treasury.index');
        Route::get('/treasury/stats', [App\Http\Controllers\Admin\TreasuryController::class, 'stats'])->name('treasury.stats');
        Route::get('/treasury/data', [App\Http\Controllers\Admin\TreasuryController::class, 'data'])->name('treasury.data');
        Route::post('/treasury/income', [TreasuryController::class, 'addIncome'])->name('treasury.income.store');
        Route::post('/treasury/expense', [TreasuryController::class, 'addExpense'])->name('treasury.expense.store');
        Route::post('/treasury/dain', [TreasuryController::class, 'addDain'])->name('treasury.dain.store');
        Route::post('/treasury/discount', [TreasuryController::class, 'addDiscount'])->name('treasury.discount.store');
        Route::get('/treasury/{transaction}', [App\Http\Controllers\Admin\TreasuryController::class, 'show'])->name('treasury.show');

        // Activity Log (العمليات)
        Route::get('/activity-log/data', [AdminActivityLog::class, 'data'])->name('activity-log.data');
        Route::get('/activity-log', [AdminActivityLog::class, 'index'])->name('activity-log.index');

        // Settings
        Route::get('/settings', [AdminSettings::class, 'index'])->name('settings.index');
        Route::post('/settings', [AdminSettings::class, 'update'])->name('settings.update');
    });

// ── Call Center ──
Route::middleware(['auth', 'role:callcenter'])
    ->prefix('callcenter')
    ->name('callcenter.')
    ->group(function () {
        Route::get('/dashboard', [CCDashboard::class, 'index'])->name('dashboard');

        // Orders
        Route::get('/orders/create', [CCOrders::class, 'create'])->name('orders.create');
        Route::get('/orders/list-data', [CCOrders::class, 'listData'])->name('orders.list-data');
        Route::get('/orders/global-search', [CCOrders::class, 'globalSearch'])->name('orders.global-search');
        Route::get('/orders/global-search/{id}', [CCOrders::class, 'globalShow'])->name('orders.global-show');
        Route::get('/orders', [CCOrders::class, 'index'])->name('orders.index');
        Route::post('/orders', [CCOrders::class, 'store'])->name('orders.store');
        Route::get('/orders/{id}', [CCOrders::class, 'show'])->name('orders.show');
        Route::put('/orders/{id}', [CCOrders::class, 'update'])->name('orders.update');
        Route::patch('/orders/{id}/cancel', [CCOrders::class, 'cancel'])->name('orders.cancel');
        Route::patch('/orders/{id}/send-early', [CCOrders::class, 'sendEarly'])->name('orders.send-early');

        // Clients
        Route::get('/clients/search', [CCClients::class, 'searchByPhone'])->name('clients.search');
        Route::get('/clients', [CCClients::class, 'index'])->name('clients.index');
        Route::post('/clients', [CCClients::class, 'store'])->name('clients.store');
        Route::get('/clients/{id}', [CCClients::class, 'show'])->name('clients.show');
        Route::put('/clients/{id}', [CCClients::class, 'update'])->name('clients.update');

        // Shops
        Route::get('/shops/active', [CCShops::class, 'active'])->name('shops.active');
        Route::get('/shops', [CCShops::class, 'index'])->name('shops.index');
        Route::post('/shops', [CCShops::class, 'store'])->name('shops.store');
        Route::post('/shop-categories', [CCShops::class, 'storeCategory'])->name('shop-categories.store');

        // Delivery
        Route::get('/delivery/active', [CCDelivery::class, 'active'])->name('delivery.active');
        Route::get('/delivery/all', [CCDelivery::class, 'allForCC'])->name('delivery.all');
        Route::patch('/delivery/{id}/toggle', [CCDelivery::class, 'toggleShift'])->name('delivery.toggle');
        Route::get('/delivery/{id}/settlement', [CCDelivery::class, 'settlement'])->name('delivery.settlement');
        Route::post('/delivery/{id}/settlement', [CCDelivery::class, 'doSettlement'])->name('delivery.do-settlement');
        Route::get('/delivery', [CCDelivery::class, 'index'])->name('delivery.index');

        // Stats
        Route::get('/stats/data', [CCStats::class, 'data'])->name('stats.data');
        Route::get('/stats', [CCStats::class, 'index'])->name('stats.index');
    });

// ── Delivery ──
Route::middleware(['auth', 'role:delivery'])
    ->prefix('delivery')
    ->name('delivery.')
    ->group(function () {
        // Shift
        Route::post('/shift/start', [\App\Http\Controllers\Delivery\ShiftController::class, 'start'])->name('shift.start');
        Route::post('/shift/end', [\App\Http\Controllers\Delivery\ShiftController::class, 'end'])->name('shift.end');
        Route::get('/shift/status', [\App\Http\Controllers\Delivery\ShiftController::class, 'status'])->name('shift.status');

        // Dashboard
        Route::get('/dashboard', [DeliveryDashboard::class, 'index'])->name('dashboard');
        Route::get('/dashboard/data', [DeliveryDashboard::class, 'data'])->name('dashboard.data');

        // New Orders
        Route::get('/orders/new', [\App\Http\Controllers\Delivery\OrderController::class, 'newOrders'])->name('orders.new');
        Route::get('/orders/new-data', [\App\Http\Controllers\Delivery\OrderController::class, 'newData'])->name('orders.new-data');
        Route::post('/orders/{id}/accept', [\App\Http\Controllers\Delivery\OrderController::class, 'accept'])->name('orders.accept');

        // Received Orders
        Route::get('/orders/received', [\App\Http\Controllers\Delivery\OrderController::class, 'received'])->name('orders.received');
        Route::get('/orders/received-data', [\App\Http\Controllers\Delivery\OrderController::class, 'receivedData'])->name('orders.received-data');
        Route::post('/orders/{id}/deliver', [\App\Http\Controllers\Delivery\OrderController::class, 'deliver'])->name('orders.deliver');
        Route::post('/orders/{id}/cancel', [\App\Http\Controllers\Delivery\OrderController::class, 'cancel'])->name('orders.cancel');

        // Delivered Orders
        Route::get('/orders/delivered', [\App\Http\Controllers\Delivery\OrderController::class, 'delivered'])->name('orders.delivered');
        Route::get('/orders/delivered-data', [\App\Http\Controllers\Delivery\OrderController::class, 'deliveredData'])->name('orders.delivered-data');
    });

// ── Reserve Delivery ──
Route::middleware(['auth', 'role:reserve_delivery'])
    ->prefix('reserve')
    ->name('reserve.')
    ->group(function () {
        // Shift
        Route::post('/shift/start', [\App\Http\Controllers\ReserveDelivery\ShiftController::class, 'start'])->name('shift.start');
        Route::post('/shift/end', [\App\Http\Controllers\ReserveDelivery\ShiftController::class, 'end'])->name('shift.end');
        Route::get('/shift/status', [\App\Http\Controllers\ReserveDelivery\ShiftController::class, 'status'])->name('shift.status');

        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\ReserveDelivery\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/data', [\App\Http\Controllers\ReserveDelivery\DashboardController::class, 'data'])->name('dashboard.data');

        // New Orders
        Route::get('/orders/new', [\App\Http\Controllers\ReserveDelivery\OrderController::class, 'newOrders'])->name('orders.new');
        Route::get('/orders/new-data', [\App\Http\Controllers\ReserveDelivery\OrderController::class, 'newData'])->name('orders.new-data');
        Route::post('/orders/{id}/accept', [\App\Http\Controllers\ReserveDelivery\OrderController::class, 'accept'])->name('orders.accept');

        // Received Orders
        Route::get('/orders/received', [\App\Http\Controllers\ReserveDelivery\OrderController::class, 'received'])->name('orders.received');
        Route::get('/orders/received-data', [\App\Http\Controllers\ReserveDelivery\OrderController::class, 'receivedData'])->name('orders.received-data');
        Route::post('/orders/{id}/deliver', [\App\Http\Controllers\ReserveDelivery\OrderController::class, 'deliver'])->name('orders.deliver');
        Route::post('/orders/{id}/cancel', [\App\Http\Controllers\ReserveDelivery\OrderController::class, 'cancel'])->name('orders.cancel');

        // Delivered Orders
        Route::get('/orders/delivered', [\App\Http\Controllers\ReserveDelivery\OrderController::class, 'delivered'])->name('orders.delivered');
        Route::get('/orders/delivered-data', [\App\Http\Controllers\ReserveDelivery\OrderController::class, 'deliveredData'])->name('orders.delivered-data');
    });