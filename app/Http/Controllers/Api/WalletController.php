<?php

namespace App\Http\Controllers\Api;

use App\Models\Anggota;
use App\Services\DokuClient;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletController extends BaseController
{
    protected function creditDompet(int $koperasiId, int $anggotaId, int $amount, int $trxGatewayId, string $keterangan = 'Topup VA'): void
    {
        $exists = DB::table('dompet')->where('koperasi_id', $koperasiId)->where('anggota_id', $anggotaId)->first();
        if (! $exists) {
            DB::table('dompet')->insert([
                'koperasi_id' => $koperasiId,
                'anggota_id' => $anggotaId,
                'saldo' => 0,
            ]);
        }
        DB::table('dompet')
            ->where('koperasi_id', $koperasiId)
            ->where('anggota_id', $anggotaId)
            ->update([
                'saldo' => DB::raw('saldo + '.(int) $amount),
            ]);
        DB::table('transaksi_dompet')->insert([
            'koperasi_id' => $koperasiId,
            'dompet_id' => DB::table('dompet')->where('koperasi_id', $koperasiId)->where('anggota_id', $anggotaId)->value('id'),
            'jenis' => 'TOPUP',
            'jumlah' => (int) $amount,
            'referensi_tipe' => 'transaksi_gateway',
            'referensi_id' => $trxGatewayId,
            'keterangan' => $keterangan,
            'created_at' => now(),
        ]);
    }

    public function createTopupVa(Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1000'],
            'channel' => ['nullable', 'string'],
        ]);
        $koperasiId = (string) $request->attributes->get('koperasi_id', '');
        if ($koperasiId === '') {
            return response()->json(['message' => 'koperasi_id is required'], 400);
        }
        $client = new DokuClient;
        if (! $client->isConfigured($koperasiId)) {
            return response()->json(['message' => 'Payment gateway DOKU belum dikonfigurasi untuk koperasi ini'], 422);
        }
        $user = $request->user();
        $anggotaAttr = $request->attributes->get('anggota_profile');
        $anggota = null;
        if (is_array($anggotaAttr) && isset($anggotaAttr['id'])) {
            $anggota = $anggotaAttr;
        } elseif ($user && $user instanceof Anggota) {
            $anggota = [
                'id' => $user->id,
                'nama' => $user->nama_anggota,
                'email' => $user->email,
                'telepon' => $user->telepon ?? '',
            ];
        } else {
            return response()->json(['message' => 'Hanya anggota yang dapat topup dompet'], 403);
        }

        $channel = $data['channel'] ?? 'VIRTUAL_ACCOUNT_BRI';
        $amount = (int) $data['amount'];
        $result = $client->createTopupVa($koperasiId, $anggota, $amount, $channel);
        if (! $result) {
            return response()->json(['message' => 'Gagal membuat VA topup (gateway error)'], 502);
        }

        $trxId = $result['trx_id'];
        $tid = DB::table('transaksi_gateway')->insertGetId([
            'koperasi_id' => (int) $koperasiId,
            'gateway_id' => 0,
            'tipe_transaksi' => 'TOPUP_DOMPET',
            'referensi_id' => (int) $anggota['id'],
            'nomor_invoice' => $trxId,
            'external_id' => $result['virtual_account_no'],
            'jumlah' => $amount,
            'response_payload' => json_encode($result),
            'status' => 'PENDING',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'id' => $tid,
            'va_number' => $result['virtual_account_no'],
            'amount' => $result['amount'],
            'how_to_pay' => $result['how_to_pay_page'],
            'expired_date' => $result['expired_date'],
            'channel' => $result['channel'],
            'invoice' => $trxId,
        ]);
    }

    public function checkTopupVaStatus(Request $request)
    {
        $payload = $request->validate([
            'invoice' => ['nullable', 'string'],
            'va_number' => ['nullable', 'string'],
        ]);
        $koperasiId = (string) $request->attributes->get('koperasi_id', '');
        if ($koperasiId === '') {
            return response()->json(['message' => 'koperasi_id is required'], 400);
        }
        $q = DB::table('transaksi_gateway')
            ->where('koperasi_id', (int) $koperasiId)
            ->where('tipe_transaksi', 'TOPUP_DOMPET');
        if (! empty($payload['invoice'])) {
            $q->where('nomor_invoice', $payload['invoice']);
        } elseif (! empty($payload['va_number'])) {
            $q->where('external_id', $payload['va_number']);
        } else {
            return response()->json(['message' => 'invoice atau va_number wajib'], 422);
        }
        $trx = $q->first();
        if (! $trx) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }
        $extra = json_decode($trx->response_payload ?? '{}', true);
        $client = new DokuClient;
        $status = $client->checkVaStatus(
            (string) $koperasiId,
            (string) ($extra['partner_service_id'] ?? ''),
            (string) ($extra['customer_no'] ?? ''),
            (string) ($trx->external_id ?? '')
        );
        if (! $status) {
            return response()->json(['message' => 'Gagal cek status ke gateway'], 502);
        }
        $paid = (float) $status['paid_value'];
        $bill = (float) $status['bill_value'];
        $isPaid = $paid > 0 && $paid >= $bill;
        if ($isPaid && $trx->status !== 'PAID') {
            $this->creditDompet((int) $koperasiId, (int) $trx->referensi_id, (int) $trx->jumlah, (int) $trx->id, 'Topup VA berhasil');
            DB::table('transaksi_gateway')->where('id', $trx->id)->update([
                'status' => 'PAID',
                'response_payload' => json_encode(['check' => $status, 'original' => $extra]),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'status' => $isPaid ? 'PAID' : 'UNPAID',
            'paid' => $paid,
            'bill' => $bill,
        ]);
    }

    public function notifyTopupVa(Request $request)
    {
        $headers = [
            'x-client-key' => $request->header('X-CLIENT-KEY') ?? $request->header('x-client-key'),
            'x-timestamp' => $request->header('X-TIMESTAMP') ?? $request->header('x-timestamp'),
            'x-signature' => $request->header('X-SIGNATURE') ?? $request->header('x-signature'),
        ];
        $clientId = (string) ($headers['x-client-key'] ?? '');
        $timestamp = (string) ($headers['x-timestamp'] ?? '');
        $signature = (string) ($headers['x-signature'] ?? '');
        if ($clientId === '' || $timestamp === '' || $signature === '') {
            return response()->json(['message' => 'Invalid headers'], 400);
        }
        $row = DB::table('doku_settings')->where('client_id', $clientId)->first();
        if (! $row) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $tokenSvc = new \Doku\Snap\Services\TokenServices;
        $ok = $tokenSvc->compareSignatures($signature, $timestamp, $clientId, $row->public_key);
        if (! $ok) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }
        $body = $request->json()->all();
        $va = data_get($body, 'virtualAccountData.virtualAccountNo')
            ?? data_get($body, 'virtualAccountNo')
            ?? data_get($body, 'body.virtualAccountNo');
        $paidValue = (float) (data_get($body, 'virtualAccountData.paidAmount.value')
            ?? data_get($body, 'paidAmount.value')
            ?? 0);
        $billValue = (float) (data_get($body, 'virtualAccountData.billAmount.value')
            ?? data_get($body, 'billAmount.value')
            ?? 0);
        if (! $va) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }
        $koperasi = DB::table('koperasi')->where('kode_koperasi', $row->kode_koperasi)->first();
        if (! $koperasi) {
            return response()->json(['message' => 'Koperasi not found'], 404);
        }
        $trx = DB::table('transaksi_gateway')
            ->where('external_id', $va)
            ->where('koperasi_id', $koperasi->id)
            ->where('tipe_transaksi', 'TOPUP_DOMPET')
            ->first();
        if (! $trx) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $isPaid = $paidValue > 0 && $paidValue >= ($trx->jumlah ?? 0) && $paidValue >= $billValue;
        if ($isPaid && $trx->status !== 'PAID') {
            $this->creditDompet((int) $trx->koperasi_id, (int) $trx->referensi_id, (int) $trx->jumlah, (int) $trx->id, 'Topup VA terverifikasi (notifikasi)');
            DB::table('transaksi_gateway')->where('id', $trx->id)->update([
                'status' => 'PAID',
                'response_payload' => json_encode(['notify' => $body]),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['status' => $isPaid ? 'PAID' : 'IGNORED']);
    }

    public function listBankAccounts(Request $request)
    {
        $anggotaId = (int) ($request->attributes->get('anggota_id') ?? ($request->user()->id ?? 0));
        $koperasiId = (string) $request->attributes->get('koperasi_id', '');
        $items = DB::table('anggota_bank_accounts')
            ->where('anggota_id', $anggotaId)
            ->where('koperasi_id', (int) $koperasiId)
            ->orderBy('is_default', 'desc')
            ->get();

        return response()->json(['items' => $items]);
    }

    public function addBankAccount(Request $request)
    {
        $anggotaId = (int) ($request->attributes->get('anggota_id') ?? ($request->user()->id ?? 0));
        $koperasiId = (string) $request->attributes->get('koperasi_id', '');
        if ($koperasiId === '') {
            return response()->json(['message' => 'koperasi_id is required'], 400);
        }
        $payload = $request->validate([
            'bank_code' => ['required', 'string', 'max:12'],
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_holder' => ['required', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
        ]);
        $isDefault = (bool) ($payload['is_default'] ?? false);
        DB::beginTransaction();
        try {
            if ($isDefault) {
                DB::table('anggota_bank_accounts')
                    ->where('anggota_id', $anggotaId)
                    ->where('koperasi_id', (int) $koperasiId)
                    ->update(['is_default' => false]);
            }
            $id = DB::table('anggota_bank_accounts')->insertGetId([
                'koperasi_id' => (int) $koperasiId,
                'anggota_id' => $anggotaId,
                'bank_code' => $payload['bank_code'],
                'bank_name' => $payload['bank_name'],
                'account_number' => $payload['account_number'],
                'account_holder' => $payload['account_holder'],
                'is_default' => $isDefault,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();

            return response()->json(['id' => $id], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['message' => 'Gagal menyimpan rekening'], 500);
        }
    }

    public function deleteBankAccount(Request $request, $id)
    {
        $anggotaId = (int) ($request->attributes->get('anggota_id') ?? ($request->user()->id ?? 0));
        $koperasiId = (string) $request->attributes->get('koperasi_id', '');
        DB::table('anggota_bank_accounts')
            ->where('id', (int) $id)
            ->where('anggota_id', $anggotaId)
            ->where('koperasi_id', (int) $koperasiId)
            ->delete();

        return response()->json(['deleted' => true]);
    }

    public function balance(Request $request)
    {
        $anggotaId = (int) ($request->attributes->get('anggota_id') ?? ($request->user()->id ?? 0));
        $koperasiId = (string) $request->attributes->get('koperasi_id', '');
        $saldo = DB::table('dompet')->where('koperasi_id', (int) $koperasiId)->where('anggota_id', $anggotaId)->value('saldo');

        return response()->json(['saldo' => (float) ($saldo ?? 0)]);
    }

    public function transactions(Request $request)
    {
        $anggotaId = (int) ($request->attributes->get('anggota_id') ?? ($request->user()->id ?? 0));
        $koperasiId = (string) $request->attributes->get('koperasi_id', '');
        $limit = (int) ($request->query('limit', 50));
        $dompetId = DB::table('dompet')->where('koperasi_id', (int) $koperasiId)->where('anggota_id', $anggotaId)->value('id');
        if (! $dompetId) {
            return response()->json(['items' => []]);
        }
        $items = DB::table('transaksi_dompet')
            ->where('koperasi_id', (int) $koperasiId)
            ->where('dompet_id', $dompetId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return response()->json(['items' => $items]);
    }

    public function summary(Request $request)
    {
        $anggotaId = (int) ($request->attributes->get('anggota_id') ?? ($request->user()->id ?? 0));
        $koperasiId = (string) $request->attributes->get('koperasi_id', '');
        $dompet = DB::table('dompet')->where('koperasi_id', (int) $koperasiId)->where('anggota_id', $anggotaId)->first();
        $dompetId = $dompet->id ?? null;
        $saldo = (float) ($dompet->saldo ?? 0);
        $from = $request->query('from');
        $to = $request->query('to');
        $minAmount = $request->query('min_amount');
        $maxAmount = $request->query('max_amount');
        $rangeStart = $from ? date('Y-m-d 00:00:00', strtotime($from)) : now()->startOfMonth()->toDateTimeString();
        $rangeEnd = $to ? date('Y-m-d 23:59:59', strtotime($to)) : now()->endOfMonth()->toDateTimeString();
        if (! $dompetId) {
            return response()->json([
                'saldo' => 0,
                'total_topup_bulan_ini' => 0,
                'total_pengeluaran_bulan_ini' => 0,
                'total_transaksi_bulan_ini' => 0,
                'terakhir_topup' => null,
                'terakhir_pengeluaran' => null,
                'total_withdraw_bulan_ini' => 0,
            ]);
        }
        $qTopup = DB::table('transaksi_dompet')
            ->where('koperasi_id', (int) $koperasiId)
            ->where('dompet_id', $dompetId)
            ->where('jenis', 'TOPUP')
            ->whereBetween('created_at', [$rangeStart, $rangeEnd]);
        if ($minAmount) {
            $qTopup->where('jumlah', '>=', (float) $minAmount);
        }
        if ($maxAmount) {
            $qTopup->where('jumlah', '<=', (float) $maxAmount);
        }
        $totalTopup = (float) $qTopup->sum('jumlah');
        $qExpense = DB::table('transaksi_dompet')
            ->where('koperasi_id', (int) $koperasiId)
            ->where('dompet_id', $dompetId)
            ->where('jenis', '<>', 'TOPUP')
            ->whereBetween('created_at', [$rangeStart, $rangeEnd]);
        if ($minAmount) {
            $qExpense->where('jumlah', '>=', (float) $minAmount);
        }
        if ($maxAmount) {
            $qExpense->where('jumlah', '<=', (float) $maxAmount);
        }
        $totalPengeluaran = (float) $qExpense->sum('jumlah');
        $qWithdraw = DB::table('transaksi_dompet')
            ->where('koperasi_id', (int) $koperasiId)
            ->where('dompet_id', $dompetId)
            ->where('jenis', 'WITHDRAW')
            ->whereBetween('created_at', [$rangeStart, $rangeEnd]);
        if ($minAmount) {
            $qWithdraw->where('jumlah', '>=', (float) $minAmount);
        }
        if ($maxAmount) {
            $qWithdraw->where('jumlah', '<=', (float) $maxAmount);
        }
        $totalWithdraw = (float) $qWithdraw->sum('jumlah');
        $totalTransaksi = (int) DB::table('transaksi_dompet')
            ->where('koperasi_id', (int) $koperasiId)
            ->where('dompet_id', $dompetId)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->count();
        $lastTopup = DB::table('transaksi_dompet')
            ->where('koperasi_id', (int) $koperasiId)
            ->where('dompet_id', $dompetId)
            ->where('jenis', 'TOPUP')
            ->orderByDesc('created_at')
            ->value('created_at');
        $lastExpense = DB::table('transaksi_dompet')
            ->where('koperasi_id', (int) $koperasiId)
            ->where('dompet_id', $dompetId)
            ->where('jenis', '<>', 'TOPUP')
            ->orderByDesc('created_at')
            ->value('created_at');

        return response()->json([
            'saldo' => $saldo,
            'total_topup_bulan_ini' => $totalTopup,
            'total_pengeluaran_bulan_ini' => $totalPengeluaran,
            'total_transaksi_bulan_ini' => $totalTransaksi,
            'terakhir_topup' => $lastTopup,
            'terakhir_pengeluaran' => $lastExpense,
            'total_withdraw_bulan_ini' => $totalWithdraw,
        ]);
    }

    public function expenses(Request $request)
    {
        $anggotaId = (int) ($request->attributes->get('anggota_id') ?? ($request->user()->id ?? 0));
        $koperasiId = (string) $request->attributes->get('koperasi_id', '');
        $limit = (int) ($request->query('limit', 50));
        $jenis = $request->query('jenis');
        $from = $request->query('from');
        $to = $request->query('to');
        $minAmount = $request->query('min_amount');
        $maxAmount = $request->query('max_amount');
        $dompetId = DB::table('dompet')->where('koperasi_id', (int) $koperasiId)->where('anggota_id', $anggotaId)->value('id');
        if (! $dompetId) {
            return response()->json(['items' => []]);
        }
        $q = DB::table('transaksi_dompet')
            ->where('koperasi_id', (int) $koperasiId)
            ->where('dompet_id', $dompetId);
        if ($jenis) {
            $q->where('jenis', $jenis);
        } else {
            $q->where('jenis', '<>', 'TOPUP');
        }
        if ($from) {
            $q->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($from)));
        }
        if ($to) {
            $q->where('created_at', '<=', date('Y-m-d 23:59:59', strtotime($to)));
        }
        if ($minAmount) {
            $q->where('jumlah', '>=', (float) $minAmount);
        }
        if ($maxAmount) {
            $q->where('jumlah', '<=', (float) $maxAmount);
        }
        $items = $q->orderByDesc('created_at')->limit($limit)->get();

        return response()->json(['items' => $items]);
    }

    public function exportTransactions(Request $request)
    {
        $anggotaId = (int) ($request->attributes->get('anggota_id') ?? ($request->user()->id ?? 0));
        $koperasiId = (string) $request->attributes->get('koperasi_id', '');
        $jenis = $request->query('jenis');
        $from = $request->query('from');
        $to = $request->query('to');
        $minAmount = $request->query('min_amount');
        $maxAmount = $request->query('max_amount');
        $dompetId = DB::table('dompet')->where('koperasi_id', (int) $koperasiId)->where('anggota_id', $anggotaId)->value('id');
        if (! $dompetId) {
            return response()->json(['message' => 'Dompet tidak ditemukan'], 404);
        }
        $filename = 'transaksi_dompet_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($koperasiId, $dompetId, $jenis, $from, $to, $minAmount, $maxAmount) {
            $q = DB::table('transaksi_dompet')
                ->where('koperasi_id', (int) $koperasiId)
                ->where('dompet_id', $dompetId);
            if ($jenis) {
                $q->where('jenis', $jenis);
            }
            if ($from) {
                $q->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($from)));
            }
            if ($to) {
                $q->where('created_at', '<=', date('Y-m-d 23:59:59', strtotime($to)));
            }
            if ($minAmount) {
                $q->where('jumlah', '>=', (float) $minAmount);
            }
            if ($maxAmount) {
                $q->where('jumlah', '<=', (float) $maxAmount);
            }
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Waktu', 'Jenis', 'Jumlah', 'Keterangan']);
            foreach ($q->orderByDesc('created_at')->cursor() as $r) {
                fputcsv($out, [
                    $r->created_at,
                    $r->jenis,
                    (float) $r->jumlah,
                    $r->keterangan,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function requestWithdraw(Request $request)
    {
        $anggotaId = (int) ($request->attributes->get('anggota_id') ?? ($request->user()->id ?? 0));
        $koperasiId = (string) $request->attributes->get('koperasi_id', '');
        if ($koperasiId === '') {
            return response()->json(['message' => 'koperasi_id is required'], 400);
        }
        $payload = $request->validate([
            'bank_account_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:1000'],
        ]);
        $bank = DB::table('anggota_bank_accounts')
            ->where('id', $payload['bank_account_id'])
            ->where('anggota_id', $anggotaId)
            ->where('koperasi_id', (int) $koperasiId)
            ->first();
        if (! $bank) {
            throw ValidationException::withMessages(['bank_account_id' => 'Rekening tidak ditemukan']);
        }
        $id = DB::table('withdraw_requests')->insertGetId([
            'koperasi_id' => (int) $koperasiId,
            'anggota_id' => $anggotaId,
            'bank_account_id' => $bank->id,
            'amount' => (int) $payload['amount'],
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['id' => $id, 'status' => 'pending'], 201);
    }

    public function reconcilePendingTopups(Request $request)
    {
        $koperasiId = (string) $request->attributes->get('koperasi_id', '');
        if ($koperasiId === '') {
            return response()->json(['message' => 'koperasi_id is required'], 400);
        }
        $limit = (int) ($request->query('limit', 20));
        $pendings = DB::table('transaksi_gateway')
            ->where('koperasi_id', (int) $koperasiId)
            ->where('tipe_transaksi', 'TOPUP_DOMPET')
            ->where('status', 'PENDING')
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get();
        if ($pendings->isEmpty()) {
            return response()->json(['checked' => 0, 'paid' => 0, 'updated' => []]);
        }
        $client = new DokuClient;
        $checked = 0;
        $paid = 0;
        $updated = [];
        foreach ($pendings as $trx) {
            $extra = json_decode($trx->response_payload ?? '{}', true);
            $status = $client->checkVaStatus(
                (string) $koperasiId,
                (string) ($extra['partner_service_id'] ?? ''),
                (string) ($extra['customer_no'] ?? ''),
                (string) ($trx->external_id ?? '')
            );
            $checked++;
            if (! $status) {
                continue;
            }
            $isPaid = (float) ($status['paid_value'] ?? 0) >= (float) ($trx->jumlah ?? 0);
            if ($isPaid && $trx->status !== 'PAID') {
                $this->creditDompet((int) $koperasiId, (int) $trx->referensi_id, (int) $trx->jumlah, (int) $trx->id, 'Topup VA (reconcile)');
                DB::table('transaksi_gateway')->where('id', $trx->id)->update([
                    'status' => 'PAID',
                    'response_payload' => json_encode(['reconcile' => $status, 'original' => $extra]),
                    'updated_at' => now(),
                ]);
                $paid++;
                $updated[] = $trx->id;
            }
        }

        return response()->json(['checked' => $checked, 'paid' => $paid, 'updated' => $updated]);
    }

    public function notifyTopupVaTest(Request $request)
    {
        $request->validate([
            'invoice' => ['nullable', 'string'],
            'va_number' => ['nullable', 'string'],
        ]);
        $koperasiId = (string) $request->attributes->get('koperasi_id', '');
        if ($koperasiId === '') {
            return response()->json(['message' => 'koperasi_id is required'], 400);
        }
        $invoice = $request->input('invoice');
        $va = $request->input('va_number');
        $q = DB::table('transaksi_gateway')
            ->where('koperasi_id', (int) $koperasiId)
            ->where('tipe_transaksi', 'TOPUP_DOMPET');
        if ($invoice) {
            $q->where('nomor_invoice', $invoice);
        } elseif ($va) {
            $q->where('external_id', $va);
        } else {
            return response()->json(['message' => 'invoice atau va_number wajib'], 422);
        }
        $trx = $q->first();
        if (! $trx) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }
        if ($trx->status !== 'PAID') {
            $this->creditDompet((int) $trx->koperasi_id, (int) $trx->referensi_id, (int) $trx->jumlah, (int) $trx->id, 'Topup VA (test webhook)');
            DB::table('transaksi_gateway')->where('id', $trx->id)->update([
                'status' => 'PAID',
                'updated_at' => now(),
            ]);
        }

        return response()->json(['status' => 'PAID', 'id' => $trx->id]);
    }
}
