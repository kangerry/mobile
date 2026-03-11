<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\KoFoodController;
use App\Http\Controllers\Api\KoperasiController;
use App\Http\Controllers\Api\PublicConfigController;
use App\Http\Controllers\Api\SellerProductController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Middleware\ApiCors;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware([ApiCors::class])->group(function () {
    Route::options('{any}', function () {
        return response('', 204)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', '*');
    })->where('any', '.*');

    Route::get('health-check', function () {
        return response()->json([
            'status' => 'ok',
            'app' => 'komera backend',
        ]);
    });

    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthApiController::class, 'login']);
        Route::post('login-google', [AuthApiController::class, 'loginGoogle']);
        Route::get('google-client', [AuthApiController::class, 'googleClient']);
        Route::post('register-anggota', [AuthApiController::class, 'registerAnggota']);
        Route::post('register-merchant', [AuthApiController::class, 'registerMerchant']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthApiController::class, 'me']);
            Route::get('profile', [AuthApiController::class, 'profile']);
            Route::put('profile', [AuthApiController::class, 'updateProfile']);
            Route::post('logout', [AuthApiController::class, 'logout']);
            Route::post('register-device-token', [DeviceTokenController::class, 'register']);
            Route::post('apply-seller', [AuthApiController::class, 'applySeller'])->middleware([TenantMiddleware::class, 'anggota.active']);
            Route::post('switch-to-merchant', [AuthApiController::class, 'switchToMerchant'])->middleware([TenantMiddleware::class, 'anggota.active']);
        });
    });

    Route::get('public-config', [PublicConfigController::class, 'show']);

    Route::prefix('wallet')->middleware(['auth:sanctum', TenantMiddleware::class, 'anggota.active'])->group(function () {
        Route::post('topup/va', [WalletController::class, 'createTopupVa']);
        Route::post('topup/va/status', [WalletController::class, 'checkTopupVaStatus']);
        Route::post('topup/va/notify/test', [WalletController::class, 'notifyTopupVaTest']);
        Route::post('reconcile', [WalletController::class, 'reconcilePendingTopups']);
        Route::get('balance', [WalletController::class, 'balance']);
        Route::get('summary', [WalletController::class, 'summary']);
        Route::get('transactions', [WalletController::class, 'transactions']);
        Route::get('expenses', [WalletController::class, 'expenses']);
        Route::get('transactions/export', [WalletController::class, 'exportTransactions']);
        Route::get('bank-accounts', [WalletController::class, 'listBankAccounts']);
        Route::post('bank-accounts', [WalletController::class, 'addBankAccount']);
        Route::delete('bank-accounts/{id}', [WalletController::class, 'deleteBankAccount']);
        Route::post('withdraw', [WalletController::class, 'requestWithdraw']);
    });

    Route::post('wallet/topup/va/notify', [WalletController::class, 'notifyTopupVa']);

    Route::prefix('kofood')->middleware([TenantMiddleware::class])->group(function () {
        Route::get('product-image', [KoFoodController::class, 'productImage']);
        Route::get('categories', [KoFoodController::class, 'categories']);
        Route::get('merchants', [KoFoodController::class, 'merchants']);
        Route::get('search', [KoFoodController::class, 'search']);
        Route::get('merchants/{id}', [KoFoodController::class, 'merchant']);
        Route::get('merchants/{id}/products', [KoFoodController::class, 'merchantProducts']);
        Route::get('products/{id}', [KoFoodController::class, 'product']);
    });
    Route::prefix('kofood')->middleware(['auth:sanctum', TenantMiddleware::class, 'anggota.active'])->group(function () {
        Route::post('orders', [KoFoodController::class, 'createOrder']);
        Route::get('orders/my', [KoFoodController::class, 'myOrders']);
        Route::get('orders/{id}', [KoFoodController::class, 'orderDetail']);
        Route::get('orders/{id}/tracking', [KoFoodController::class, 'orderTracking']);
    });

    Route::prefix('seller')->middleware(['auth:sanctum', TenantMiddleware::class])->group(function () {
        Route::get('products', [SellerProductController::class, 'index']);
        Route::post('products', [SellerProductController::class, 'store']);
        Route::put('products/{id}', [SellerProductController::class, 'update']);
        Route::post('products/{id}/photos', [SellerProductController::class, 'uploadPhoto']);
        Route::get('products/{id}/photos', [SellerProductController::class, 'listPhotos']);
        Route::delete('products/{id}/photos/{photoId}', [SellerProductController::class, 'deletePhoto']);
        Route::get('orders', [KoFoodController::class, 'sellerOrders']);
        Route::post('orders/{id}/process', [KoFoodController::class, 'processSellerOrder']);
    });

    Route::get('koperasi', [KoperasiController::class, 'index']);
});
