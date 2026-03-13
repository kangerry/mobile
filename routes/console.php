<?php

use App\Services\DokuClient;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:url {url}', function () {
    $url = (string) $this->argument('url');
    $url = trim($url);
    if ($url === '') {
        $this->error('URL wajib diisi');
        return;
    }
    if (! Str::startsWith($url, ['http://', 'https://'])) {
        $this->error('URL harus diawali http:// atau https://');
        return;
    }
    $url = rtrim($url, '/');
    $envPath = base_path('.env');
    if (! file_exists($envPath)) {
        $this->error('.env tidak ditemukan');
        return;
    }
    $env = file_get_contents($envPath);
    $pattern = '/^APP_URL=.*$/m';
    $line = 'APP_URL='.$url;
    if (preg_match($pattern, $env)) {
        $env = preg_replace($pattern, $line, $env);
    } else {
        $env .= PHP_EOL.$line.PHP_EOL;
    }
    file_put_contents($envPath, $env);
    $this->info('APP_URL diperbarui: '.$url);
    try {
        $this->call('config:clear');
        $this->call('config:cache');
        $this->info('Konfigurasi di-refresh');
    } catch (\Throwable $e) {
        $this->warn('Gagal me-refresh config: '.$e->getMessage());
    }
})->purpose('Mengatur APP_URL dan refresh config cache');

Artisan::command('wallet:reconcile {koperasi_id} {--limit=20}', function () {
    $koperasiId = (string) $this->argument('koperasi_id');
    $limit = (int) $this->option('limit');
    $pendings = DB::table('transaksi_gateway')
        ->where('koperasi_id', (int) $koperasiId)
        ->where('tipe_transaksi', 'TOPUP_DOMPET')
        ->where('status', 'PENDING')
        ->orderBy('id', 'asc')
        ->limit($limit)
        ->get();
    if ($pendings->isEmpty()) {
        $this->info('No pending transactions');

        return;
    }
    $client = new DokuClient;
    $paid = 0;
    foreach ($pendings as $trx) {
        $extra = json_decode($trx->response_payload ?? '{}', true);
        $status = $client->checkVaStatus(
            (string) $koperasiId,
            (string) ($extra['partner_service_id'] ?? ''),
            (string) ($extra['customer_no'] ?? ''),
            (string) ($trx->external_id ?? '')
        );
        if (! $status) {
            continue;
        }
        $isPaid = (float) ($status['paid_value'] ?? 0) >= (float) ($trx->jumlah ?? 0);
        $alreadyCredited = DB::table('transaksi_dompet')
            ->where('koperasi_id', (int) $koperasiId)
            ->where('referensi_tipe', 'transaksi_gateway')
            ->where('referensi_id', (int) $trx->id)
            ->exists();
        if ($isPaid && ! $alreadyCredited) {
            DB::table('dompet')->updateOrInsert(['koperasi_id' => (int) $koperasiId, 'anggota_id' => (int) $trx->referensi_id], ['saldo' => DB::raw('COALESCE(saldo,0) + '.(int) $trx->jumlah)]);
            $dompetId = DB::table('dompet')->where('koperasi_id', (int) $koperasiId)->where('anggota_id', (int) $trx->referensi_id)->value('id');
            DB::table('transaksi_dompet')->insert(['koperasi_id' => (int) $koperasiId, 'dompet_id' => $dompetId, 'jenis' => 'TOPUP', 'jumlah' => (int) $trx->jumlah, 'referensi_tipe' => 'transaksi_gateway', 'referensi_id' => $trx->id, 'keterangan' => 'Topup VA (scheduler)', 'created_at' => now()]);
        }
        if ($isPaid && $trx->status !== 'PAID') {
            DB::table('transaksi_gateway')->where('id', $trx->id)->update(['status' => 'PAID', 'updated_at' => now()]);
            $paid++;
        }
    }
    $this->info('Checked: '.count($pendings).' | Paid: '.$paid);
})->purpose('Reconcile pending VA topups for a koperasi');

Artisan::command('wallet:reconcile:all {--limit=20}', function () {
    $limit = (int) $this->option('limit');
    $kop = DB::table('koperasi')->pluck('id');
    foreach ($kop as $k) {
        $this->call('wallet:reconcile', ['koperasi_id' => $k, '--limit' => $limit]);
    }
})->purpose('Reconcile pending VA topups for all koperasi');
