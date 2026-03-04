<?php

use App\Http\Middleware\ApiCors;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\Kojek\Http\Controllers\Api\DriverApiController;

Route::prefix('v1/kojek')->middleware([ApiCors::class, TenantMiddleware::class])->group(function () {
    Route::post('register', [DriverApiController::class, 'register']);
    Route::post('login', [DriverApiController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('online', [DriverApiController::class, 'setOnline']);
        Route::post('driver/location', [DriverApiController::class, 'updateLocation']);
        Route::get('orders/available', [DriverApiController::class, 'availableOrders']);
        Route::get('orders/my/active', [DriverApiController::class, 'myActiveOrder']);
        Route::get('orders/history', [DriverApiController::class, 'history']);
        Route::post('orders/{id}/accept', [DriverApiController::class, 'acceptOrder']);
        Route::post('orders/{id}/reject', [DriverApiController::class, 'rejectOrder']);
        Route::post('orders/{id}/complete', [DriverApiController::class, 'completeOrder']);
    });
});
