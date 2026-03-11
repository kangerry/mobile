<?php

use App\Http\Controllers\AnggotaSessionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Backoffice\ActiveKoperasiController;
use App\Http\Controllers\Backoffice\AnggotaController;
use App\Http\Controllers\Backoffice\ApprovalAnggotaController;
use App\Http\Controllers\Backoffice\ApprovalMerchantController;
use App\Http\Controllers\Backoffice\ApprovalDriverController;
use App\Http\Controllers\Backoffice\DashboardController;
use App\Http\Controllers\Backoffice\DriverController;
use App\Http\Controllers\Backoffice\GatewayLogController;
use App\Http\Controllers\Backoffice\KoperasiController;
use App\Http\Controllers\Backoffice\MerchantController;
use App\Http\Controllers\Backoffice\PesananMakananController;
use App\Http\Controllers\Backoffice\PesananOjekController;
use App\Http\Controllers\Backoffice\ProdukController;
use App\Http\Controllers\Backoffice\SetupGatewayController;
use App\Http\Controllers\Backoffice\TarifDeliveryTokoController;
use App\Http\Controllers\Backoffice\TarifKojekController;
use App\Http\Controllers\Backoffice\UserController as BackofficeUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backoffice\AccessRoleController;
use App\Http\Controllers\Backoffice\AccessPermissionController;

Route::redirect('/', '/login');

Route::get('/maps.js', function () {
    $key = env('MAPS_API_KEY', '');
    $js = <<<JS
(function(){
  var s = document.createElement('script');
  s.src = 'https://maps.googleapis.com/maps/api/js?key={$key}&v=weekly&libraries=marker';
  s.async = true; s.defer = true;
  document.head.appendChild(s);
})();
JS;
    return response($js, 200)
        ->header('Content-Type', 'application/javascript')
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/pg/simulated-checkout', function (Request $request) {
    $nomor = (string) $request->query('order', '');
    if ($nomor === '') {
        return response('Order not specified', 400);
    }
    $order = DB::table('pesanan_makanan')->where('nomor_pesanan', $nomor)->first();
    if (! $order) {
        return response('Order not found', 404);
    }
    $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Simulated Checkout</title><meta name="viewport" content="width=device-width, initial-scale=1"><style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;margin:2rem;color:#111} .box{max-width:460px;margin:auto;border:1px solid #e5e7eb;border-radius:12px;padding:20px;box-shadow:0 10px 30px rgba(0,0,0,.06)} .btn{display:inline-block;background:#2563eb;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none} .muted{color:#6b7280}</style></head><body><div class="box"><h2>Pembayaran Pesanan</h2><p class="muted">Nomor Pesanan</p><h3>'.$nomor.'</h3><p>Total Bayar</p><h3>Rp '.number_format((float) ($order->total_bayar ?? 0), 0, ',', '.').'</h3><p><a class="btn" href="/pg/simulated-checkout/pay?order='.$nomor.'">Bayar Sekarang</a></p></div></body></html>';
    return response($html, 200);
});

Route::get('/pg/simulated-checkout/pay', function (Request $request) {
    $nomor = (string) $request->query('order', '');
    if ($nomor === '') {
        return response('Order not specified', 400);
    }
    $updated = DB::table('pesanan_makanan')->where('nomor_pesanan', $nomor)->update([
        'status_pembayaran' => 'paid',
        'status' => 'diproses',
        'updated_at' => now(),
    ]);
    if (! $updated) {
        return response('Order not found', 404);
    }
    $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Pembayaran Berhasil</title><meta name="viewport" content="width=device-width, initial-scale=1"><style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;margin:2rem;color:#111;text-align:center}</style></head><body><h2>Pembayaran Berhasil</h2><p>Nomor Pesanan '.$nomor.'</p><p>Anda dapat menutup halaman ini dan kembali ke aplikasi.</p></body></html>';
    return response($html, 200);
});

Route::prefix('anggota')->group(function () {
    Route::post('login', [AnggotaSessionController::class, 'login'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
    Route::post('logout', [AnggotaSessionController::class, 'logout'])->middleware('auth:anggota')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
    Route::get('me', [AnggotaSessionController::class, 'me'])->middleware('auth:anggota');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('active-koperasi', [ActiveKoperasiController::class, 'set'])->name('active-koperasi.set');

    // Custom actions
    Route::post('setup-gateway/test-connection', [SetupGatewayController::class, 'testConnection'])->name('setup-gateway.test');
    Route::post('setup-gateway/advanced-test', [SetupGatewayController::class, 'advancedTest'])->name('setup-gateway.advanced');
    Route::post('setup-gateway/db-check', [SetupGatewayController::class, 'dbCheck'])->name('setup-gateway.dbcheck');

    Route::get('approval-anggota', [ApprovalAnggotaController::class, 'index'])->name('approval-anggota.index');
    Route::post('approval-anggota/{id}/approve', [ApprovalAnggotaController::class, 'approve'])->name('approval-anggota.approve');
    Route::post('approval-anggota/{id}/reject', [ApprovalAnggotaController::class, 'reject'])->name('approval-anggota.reject');
    Route::get('approval-merchant', [ApprovalMerchantController::class, 'index'])->name('approval-merchant.index');
    Route::post('approval-merchant/{id}/approve', [ApprovalMerchantController::class, 'approve'])->name('approval-merchant.approve');
    Route::post('approval-merchant/{id}/reject', [ApprovalMerchantController::class, 'reject'])->name('approval-merchant.reject');
    Route::get('approval-driver', [ApprovalDriverController::class, 'index'])->name('approval-driver.index');
    Route::post('approval-driver/{id}/approve', [ApprovalDriverController::class, 'approve'])->name('approval-driver.approve');
    Route::post('approval-driver/{id}/reject', [ApprovalDriverController::class, 'reject'])->name('approval-driver.reject');

    Route::resource('koperasi', KoperasiController::class);
    Route::resource('anggota', AnggotaController::class);
    Route::resource('merchant', MerchantController::class);
    Route::resource('produk', ProdukController::class);
    Route::resource('kategori-produk', \App\Http\Controllers\Backoffice\KategoriProdukController::class);
    Route::resource('pesanan-makanan', PesananMakananController::class);
    Route::get('pesanan-makanan/delivery-board', [PesananMakananController::class, 'deliveryBoard'])->name('pesanan-makanan.delivery-board');
    Route::post('pesanan-makanan/{id}/assign-driver', [PesananMakananController::class, 'assignDriver'])->name('pesanan-makanan.assign-driver');
    Route::post('pesanan-makanan/{id}/complete', [PesananMakananController::class, 'completeDelivery'])->name('pesanan-makanan.complete');
    Route::resource('driver', DriverController::class);
    Route::get('driver/monitoring', [DriverController::class, 'monitoring'])->name('driver.monitoring');
    Route::get('driver/positions', [DriverController::class, 'positions'])->name('driver.positions');
    Route::resource('pesanan-ojek', PesananOjekController::class);
    Route::resource('setup-gateway', SetupGatewayController::class);
    Route::resource('tarif-kojek', TarifKojekController::class);
    Route::resource('tarif-delivery-toko', TarifDeliveryTokoController::class);
    Route::get('gateway-logs', [GatewayLogController::class, 'index'])->name('gateway-logs.index');
    Route::get('gateway-logs/export', [GatewayLogController::class, 'export'])->name('gateway-logs.export');
    Route::resource('users', BackofficeUserController::class);
    Route::resource('access-roles', AccessRoleController::class)->except(['show']);
    Route::get('permissions', [AccessPermissionController::class, 'index'])->name('permissions.index');
    Route::post('permissions', [AccessPermissionController::class, 'store'])->name('permissions.store');
    Route::delete('permissions/{id}', [AccessPermissionController::class, 'destroy'])->name('permissions.destroy');
});
