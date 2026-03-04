<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifikasi\Http\Controllers\NotifikasiController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('notifikasis', NotifikasiController::class)->names('notifikasi');
});
