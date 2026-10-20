<?php

use App\Http\Controllers\AccountSettingsController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\KitchenBatchNotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductIngredientController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockNotificationController;
use App\Http\Controllers\TransactionReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// =====================================================
// LOGIN
// =====================================================

Route::get(
    '/login',
    [AuthController::class, 'showLogin']
)->name('login');

Route::post(
    '/login',
    [AuthController::class, 'login']
)->name('login.store');

Route::post(
    '/logout',
    [AuthController::class, 'logout']
)
    ->middleware('auth')
    ->name('logout');

// =====================================================
// PROTECTED SYSTEM ROUTES
// =====================================================

Route::middleware('auth')->group(function () {

    Route::get(
        '/account-settings',
        [AccountSettingsController::class, 'edit']
    )->name('account-settings.edit');

    Route::put(
        '/account-settings',
        [AccountSettingsController::class, 'update']
    )->name('account-settings.update');

    Route::middleware('role:Cashier')->group(function () {
        Route::get(
            '/kitchen-batch-notifications',
            [KitchenBatchNotificationController::class, 'index']
        )->name('kitchen-batch-notifications.index');

        Route::post(
            '/kitchen-batch-notifications/{notification}/read',
            [KitchenBatchNotificationController::class, 'markAsRead']
        )->name('kitchen-batch-notifications.read');
    });

    // =================================================
    // DASHBOARD
    // =================================================

    Route::get('/', DashboardController::class)->name('dashboard');

    // =================================================
    // MANAGER ONLY
    // =================================================

    Route::middleware('role:Manager')->group(function () {
        // =================================================
        // DAILY TRANSACTION REPORT
        // =================================================

        Route::get(
            '/reports/transactions',
            TransactionReportController::class
        )->name('reports.transactions');

        Route::get(
            '/reports/sales',
            SalesReportController::class
        )->name('reports.sales');

        Route::resource(
            'roles',
            RoleController::class
        );
        Route::resource(
            'employees',
            EmployeeController::class
        );

        Route::resource(
            'schedules',
            ScheduleController::class
        );

        Route::resource(
            'products',
            ProductController::class
        );

        Route::resource(
            'ingredients',
            IngredientController::class
        );

        Route::resource(
            'inventories',
            InventoryController::class
        );

        Route::resource(
            'stock-movements',
            StockMovementController::class
        );
        Route::resource('users',
            UserController::class
        );

        // =================================================
        // STOCK NOTIFICATIONS
        // =================================================

        Route::get(
            '/stock-notifications',
            [StockNotificationController::class, 'index']
        )->name('stock-notifications.index');

        Route::resource(
            'product-ingredients',
            ProductIngredientController::class
        );
    });

    // =================================================
    // MANAGER + CASHIER
    // =================================================

    Route::middleware('role:Manager,Cashier')->group(function () {

        Route::get(
            'orders',
            [OrderController::class, 'index']
        )->name('orders.index');

        Route::get(
            'payments/{payment}/proof',
            [PaymentController::class, 'proof']
        )->name('payments.proof');

        Route::resource(
            'payments',
            PaymentController::class
        );
    });

    Route::middleware('role:Cashier')->group(function () {

        Route::resource(
            'orders',
            OrderController::class
        )->except(['index']);

        Route::get(
            'orders/{order}/completed',
            [OrderController::class, 'completed']
        )->name('orders.completed');

        Route::post(
            'orders/{order}/confirm',
            [OrderController::class, 'confirm']
        )->name('orders.confirm');

        Route::post(
            'orders/{order}/complete',
            [OrderController::class, 'complete']
        )->name('orders.complete');

        Route::resource(
            'order-items',
            OrderItemController::class
        );
    });

    // =================================================
    // MANAGER + KITCHEN STAFF
    // =================================================

    Route::middleware('role:Manager,Kitchen Staff')->group(function () {

        Route::post(
            '/kitchen/batches',
            [OrderController::class, 'startCookingBatch']
        )->name('kitchen.batches.store');

        Route::post(
            '/kitchen/batches/{batch}/ready',
            [OrderController::class, 'markCookingBatchReady']
        )->name('kitchen.batches.ready');

        Route::delete(
            '/kitchen/batches/reset',
            [OrderController::class, 'resetCookingBatches']
        )->name('kitchen.batches.reset');

        Route::get(
            '/kitchen',
            [OrderController::class, 'kitchen']
        )->name('kitchen.index');

        Route::post(
            'orders/{order}/preparing',
            [OrderController::class, 'preparing']
        )->name('orders.preparing');

        Route::post(
            'orders/{order}/ready',
            [OrderController::class, 'ready']
        )->name('orders.ready');
    });

    // =================================================
    // ATTENDANCE
    // =================================================

    Route::post(
        'attendances/{attendance}/clock-out',
        [AttendanceController::class, 'clockOut']
    )->name('attendances.clock-out');

    Route::post(
        'attendances/{attendance}/upload-proof',
        [AttendanceController::class, 'uploadProof']
    )->name('attendances.upload-proof');

    Route::resource(
        'attendances',
        AttendanceController::class
    );
});
