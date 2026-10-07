<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\ProductIngredientController;
use App\Http\Controllers\StockNotificationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\UserController;


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
// FIRST-TIME SETUP (only works when 0 users exist)
// =====================================================

Route::get(
    '/register',
    [RegisterController::class, 'showForm']
)->name('register');

Route::post(
    '/register',
    [RegisterController::class, 'register']
)->name('register.store');


// =====================================================
// PROTECTED SYSTEM ROUTES
// =====================================================

Route::middleware('auth')->group(function () {

    // =================================================
    // DASHBOARD
    // =================================================

    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');


    // =================================================
    // MANAGER ONLY
    // =================================================

    Route::middleware('role:Manager')->group(function () {

    Route::resource(
    'roles',
    \App\Http\Controllers\RoleController::class
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

        Route::resource(
            'orders',
            OrderController::class
        );

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

        Route::resource(
            'payments',
            PaymentController::class
        );
    });


    // =================================================
    // MANAGER + KITCHEN STAFF
    // =================================================

    Route::middleware('role:Manager,Kitchen Staff')->group(function () {

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