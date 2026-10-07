<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LaundryOrderController;
use App\Http\Controllers\Api\ServiceTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:5,1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/services', [ServiceTypeController::class, 'index']);
    Route::get('/orders', [LaundryOrderController::class, 'index']);
    Route::post('/orders', [LaundryOrderController::class, 'store']);
    Route::get('/orders/{laundryOrder}', [LaundryOrderController::class, 'show']);

    Route::middleware('role:admin,staff')->group(function () {
        Route::post('/services', [ServiceTypeController::class, 'store']);
        Route::match(['put', 'patch'], '/services/{serviceType}', [ServiceTypeController::class, 'update']);
        Route::patch('/orders/{laundryOrder}/status', [LaundryOrderController::class, 'transition']);
        Route::post('/orders/{laundryOrder}/payments', [LaundryOrderController::class, 'pay']);
        Route::get('/dashboard', DashboardController::class);
    });
});
