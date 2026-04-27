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
        Route::get('/orders/{id}/pdf', [AdminOrders::class, 'downloadPdf'])->name('orders.pdf');
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
        Route::patch('/delivery/{id}/toggle-shift', [AdminDelivery::class, 'toggleShift'])->name('delivery.toggle-shift');

        // Call Center
        Route::get('/callcenter', [AdminCallCenter::class, 'index'])->name('callcenter.index');
        Route::post('/callcenter', [AdminCallCenter::class, 'store'])->name('callcenter.store');
        Route::put('/callcenter/{id}', [AdminCallCenter::class, 'update'])->name('callcenter.update');
        Route::get('/callcenter/{id}/performance', [AdminCallCenter::class, 'performance'])->name('callcenter.performance');
        Route::patch('/callcenter/{id}/toggle-shift', [AdminCallCenter::class, 'toggleShift'])->name('callcenter.toggle-shift');

        // Admin Management (المديرين)
        Route::get('/admin-management', [\App\Http\Controllers\Admin\AdminManagementController::class, 'index'])->name('admin-management.index');
        Route::post('/admin-management', [\App\Http\Controllers\Admin\AdminManagementController::class, 'store'])->name('admin-management.store');
        Route::put('/admin-management/{id}', [\App\Http\Controllers\Admin\AdminManagementController::class, 'update'])->name('admin-management.update');

        // Reports
        Route::get('/reports/data', [AdminReports::class, 'data'])->name('reports.data');
        Route::get('/reports/export-pdf', [AdminReports::class, 'exportPdf'])->name('reports.export-pdf');
        Route::get('/reports', [AdminReports::class, 'index'])->name('reports.index');

        // Delivery Reports
        Route::get('/report-delivery/data', [App\Http\Controllers\Admin\ReportDeliveryController::class, 'data'])->name('report-delivery.data');
        Route::get('/report-delivery', [App\Http\Controllers\Admin\ReportDeliveryController::class, 'index'])->name('report-delivery.index');

        // Call Center Reports
        Route::get('/report-callcenter/data', [App\Http\Controllers\Admin\ReportCallCenterController::class, 'data'])->name('report-callcenter.data');
        Route::get('/report-callcenter', [App\Http\Controllers\Admin\ReportCallCenterController::class, 'index'])->name('report-callcenter.index');

        // Report Hops
        Route::get('/report-hops/data', [AdminReportHops::class, 'data'])->name('report-hops.data');
        Route::get('/report-hops/{shopId}/pdf', [AdminReportHops::class, 'exportPdf'])->name('report-hops.pdf');
        Route::get('/report-hops/{shopId}/due-invoice', [AdminReportHops::class, 'dueInvoicePdf'])->name('report-hops.due-invoice');
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

        Route::post('/treasury/pay-to-user', [TreasuryController::class, 'payToUser'])->name('treasury.pay-to-user');
        Route::post('/treasury/receive-from-user', [TreasuryController::class, 'receiveFromUser'])->name('treasury.receive-from-user');
        Route::get('/treasury/{transaction}', [App\Http\Controllers\Admin\TreasuryController::class, 'show'])->name('treasury.show');
        Route::patch('/treasury/{transaction}', [TreasuryController::class, 'update'])->name('treasury.update');
        Route::get('/treasury/{transaction}/pdf', [TreasuryController::class, 'exportPdf'])->name('treasury.pdf');

        // Trial Balance Report (ميزان المراجعة)
        Route::get('/report-trial-balance/data', [\App\Http\Controllers\Admin\ReportTrialBalanceController::class, 'data'])->name('report-trial-balance.data');
        Route::get('/report-trial-balance', [\App\Http\Controllers\Admin\ReportTrialBalanceController::class, 'index'])->name('report-trial-balance.index');

        // General Ledger (كشف حساب عام)
        Route::get('/general-ledger', [App\Http\Controllers\Admin\GeneralLedgerController::class, 'index'])->name('general-ledger.index');
        Route::get('/general-ledger/data', [App\Http\Controllers\Admin\GeneralLedgerController::class, 'data'])->name('general-ledger.data');
        Route::get('/general-ledger/user/{userId}', [App\Http\Controllers\Admin\GeneralLedgerController::class, 'userStatement'])->name('general-ledger.user');
        Route::get('/general-ledger/treasury', [App\Http\Controllers\Admin\GeneralLedgerController::class, 'treasuryStatement'])->name('general-ledger.treasury');

        // Activity Log (العمليات)
        Route::get('/activity-log/data', [AdminActivityLog::class, 'data'])->name('activity-log.data');
        Route::get('/activity-log', [AdminActivityLog::class, 'index'])->name('activity-log.index');

        // Settings
        Route::get('/settings', [AdminSettings::class, 'index'])->name('settings.index');
        Route::post('/settings', [AdminSettings::class, 'update'])->name('settings.update');

        // Admin Notifications
        Route::get('/notifications', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/count', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'count'])->name('notifications.count');
        Route::post('/notifications/read-all', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'markAllRead'])->name('notifications.read-all');
    });

// ── Call Center ──
Route::middleware(['auth', 'role:callcenter'])
    ->prefix('callcenter')
    ->name('callcenter.')
    ->group(function () {
        Route::get('/dashboard', [CCDashboard::class, 'index'])->name('dashboard');

        // Shift
        Route::post('/shift/toggle', [\App\Http\Controllers\CallCenter\ShiftController::class, 'toggle'])->name('shift.toggle');
        Route::get('/shift/status', [\App\Http\Controllers\CallCenter\ShiftController::class, 'status'])->name('shift.status');

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
        Route::get('/orders/{id}/pdf', [CCOrders::class, 'downloadPdf'])->name('orders.pdf');

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
        Route::get('/delivery/{id}/statement', [CCDelivery::class, 'statement'])->name('delivery.statement');
        Route::get('/delivery', [CCDelivery::class, 'index'])->name('delivery.index');

        // Stats
        Route::get('/stats/data', [CCStats::class, 'data'])->name('stats.data');
        Route::get('/stats', [CCStats::class, 'index'])->name('stats.index');

        // Wallet (كشف حسابي)
        Route::get('/wallet', [\App\Http\Controllers\CallCenter\WalletController::class, 'index'])->name('wallet.index');
        Route::get('/wallet/statement', [\App\Http\Controllers\CallCenter\WalletController::class, 'statement'])->name('wallet.statement');
        Route::post('/wallet/pay-delivery', [\App\Http\Controllers\CallCenter\WalletController::class, 'payToDelivery'])->name('wallet.pay-delivery');
        Route::post('/wallet/receive-delivery', [\App\Http\Controllers\CallCenter\WalletController::class, 'receiveFromDelivery'])->name('wallet.receive-delivery');
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
        Route::get('/orders/{id}/invoice/download', [\App\Http\Controllers\Delivery\OrderController::class, 'downloadInvoice'])->name('orders.invoice.download');

        // Wallet (كشف حسابي)
        Route::get('/wallet', [\App\Http\Controllers\Delivery\WalletController::class, 'index'])->name('wallet.index');
        Route::get('/wallet/statement', [\App\Http\Controllers\Delivery\WalletController::class, 'statement'])->name('wallet.statement');
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
        Route::get('/orders/{id}/invoice/download', [\App\Http\Controllers\ReserveDelivery\OrderController::class, 'downloadInvoice'])->name('orders.invoice.download');

        // Wallet (كشف حسابي)
        Route::get('/wallet', [\App\Http\Controllers\ReserveDelivery\WalletController::class, 'index'])->name('wallet.index');
        Route::get('/wallet/statement', [\App\Http\Controllers\ReserveDelivery\WalletController::class, 'statement'])->name('wallet.statement');
    });