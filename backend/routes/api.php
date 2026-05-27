<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\BorrowingController;
use App\Http\Controllers\Api\CupboardController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\PlaceController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\AdminOnly;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected Routes (require authentication)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // User Management (Admin only)
    Route::middleware(AdminOnly::class)->group(function () {
        Route::apiResource('users', UserController::class);
    });

    // Cupboards
    Route::apiResource('cupboards', CupboardController::class);

    // Places
    Route::apiResource('places', PlaceController::class);

    // Items
    Route::apiResource('items', ItemController::class);
    Route::patch('/items/{item}/quantity', [ItemController::class, 'updateQuantity']);
    Route::patch('/items/{item}/status', [ItemController::class, 'updateStatus']);

    // Borrowings
    Route::get('/borrowings', [BorrowingController::class, 'index']);
    Route::post('/borrowings', [BorrowingController::class, 'store']);
    Route::get('/borrowings/{borrowing}', [BorrowingController::class, 'show']);
    Route::patch('/borrowings/{borrowing}/return', [BorrowingController::class, 'returnItem']);

    // Audit Logs
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
});
