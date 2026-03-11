<?php

namespace App\Http\Controllers\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class SetupGatewayController extends BaseController
{
    public function index(Request $request)
    {
        $kode = $request->query('kode_koperasi');
        if ($kode) {
            $kode = strtoupper(trim((string) $kode));
        }
        $values = [
            'kode_koperasi' => $kode ?? '',
            'DOKU_ENV' => 'sandbox',
            'DOKU_CLIENT_ID' => '',
            'DOKU_SECRET_KEY' => '',
            'DOKU_API_KEY' => '',
            'DOKU_PRIVATE_KEY' => '',
            'DOKU_PUBLIC_KEY' => '',
            'DOKU_BASE_URL' => '',
            'ALLOW_SANDBOX_SIM' => 1,
        ];
        if ($kode) {
            try {
                $row = DB::table('doku_settings')->where('kode_koperasi', $kode)->first();
                if ($row) {
                    $values = [
                        'kode_koperasi' => $row->kode_koperasi,
                        'DOKU_ENV' => $row->env,
                        'DOKU_CLIENT_ID' => $row->client_id,
                        'DOKU_SECRET_KEY' => $row->secret_key,
                        'DOKU_API_KEY' => $row->api_key,
                        'DOKU_PRIVATE_KEY' => $row->private_key ?? '',
                        'DOKU_PUBLIC_KEY' => $row->public_key,
                        'DOKU_BASE_URL' => $row->base_url,
                        'ALLOW_SANDBOX_SIM' => isset($row->allow_sandbox_simulation) ? (int) $row->allow_sandbox_simulation : 1,
                    ];
                }
            } catch (\Throwable $e) {
            }
        }

        return view('setup_gateway.index', compact('values', 'kode'));
    }

    public function create()
    {
        return redirect()->route('setup-gateway.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode_koperasi' => 'required|string',
            'DOKU_ENV' => 'required|in:sandbox,production',
            'DOKU_CLIENT_ID' => 'required|string',
            'DOKU_SECRET_KEY' => 'required|string',
            'DOKU_API_KEY' => 'required|string',
            'DOKU_PRIVATE_KEY' => 'required_if:DOKU_ENV,production|nullable|string',
            'DOKU_PUBLIC_KEY' => 'required|string',
            'DOKU_BASE_URL' => 'required|string',
        ]);

        $data['kode_koperasi'] = strtoupper(trim($data['kode_koperasi']));
        $base = trim(str_replace(['`', ' '], '', $data['DOKU_BASE_URL']));
        $pub = $data['DOKU_PUBLIC_KEY'];
        $pub = str_replace(["\r\n", "\r"], "\n", $pub);
        $priv = str_replace(["\r\n", "\r"], "\n", $data['DOKU_PRIVATE_KEY']);

        $payload = [
            'env' => $data['DOKU_ENV'],
            'client_id' => $data['DOKU_CLIENT_ID'],
            'secret_key' => $data['DOKU_SECRET_KEY'],
            'api_key' => $data['DOKU_API_KEY'],
            'public_key' => $pub,
            'base_url' => $base,
            'updated_at' => now(),
            'created_at' => now(),
        ];
        if (Schema::hasColumn('doku_settings', 'private_key')) {
            $payload['private_key'] = $priv;
        }
        if (Schema::hasColumn('doku_settings', 'allow_sandbox_simulation')) {
            $payload['allow_sandbox_simulation'] = $request->has('ALLOW_SANDBOX_SIM') ? 1 : 0;
        }
        try {
            DB::table('doku_settings')->updateOrInsert(['kode_koperasi' => $data['kode_koperasi']], $payload);
        } catch (\Throwable $e) {
            return redirect()->back()->with('status', 'Gagal menyimpan ke database: '.$e->getMessage());
        }

        return redirect()->route('setup-gateway.index', ['kode_koperasi' => $data['kode_koperasi']])->with('status', 'Konfigurasi DOKU tersimpan untuk koperasi '.$data['kode_koperasi']);
    }

    public function dbCheck(Request $request)
    {
        $kode = strtoupper(trim((string) $request->input('kode_koperasi', '')));
        $default = config('database.default');
        $conn = config("database.connections.{$default}");
        $dbName = (string) ($conn['database'] ?? '');
        $driver = (string) ($conn['driver'] ?? 'unknown');
        $hasTable = \Illuminate\Support\Facades\Schema::hasTable('doku_settings');
        $expected = ['kode_koperasi', 'env', 'client_id', 'secret_key', 'api_key', 'public_key', 'base_url', 'private_key', 'allow_sandbox_simulation', 'created_at', 'updated_at'];
        $cols = [];
        if ($hasTable) {
            foreach ($expected as $c) {
                $cols[$c] = \Illuminate\Support\Facades\Schema::hasColumn('doku_settings', $c) ? 'OK' : 'MISSING';
            }
        }
        $count = 0;
        if ($hasTable && $kode !== '') {
            try {
                $count = (int) DB::table('doku_settings')->where('kode_koperasi', $kode)->count();
            } catch (\Throwable $e) {
            }
        }
        $msg = "DB driver={$driver}, database={$dbName}. Table doku_settings: ".($hasTable ? 'ADA' : 'TIDAK ADA').'.';
        if ($hasTable) {
            $missing = array_keys(array_filter($cols, fn ($v) => $v === 'MISSING'));
            $msg .= ' Kolom hilang: '.(empty($missing) ? '-' : implode(',', $missing)).'.';
            if ($kode !== '') {
                $msg .= " Baris untuk {$kode}: {$count}.";
            }
        }

        return redirect()->back()->with('status', $msg);
    }

    public function testConnection(Request $request)
    {
        $payload = $request->validate([
            'kode_koperasi' => 'required|string',
        ]);
        $row = DB::table('doku_settings')->where('kode_koperasi', $payload['kode_koperasi'])->first();
        if (! $row) {
            return redirect()->back()->with('status', 'Data DOKU untuk koperasi '.$payload['kode_koperasi'].' belum ditemukan');
        }
        $url = rtrim((string) $row->base_url, '/').'/';
        try {
            $start = microtime(true);
            $resp = Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => 'Komera-DOKU-Connectivity/1.0',
                    'Client-Id' => $row->client_id,
                ])
                ->get($url);
            $ms = (int) round((microtime(true) - $start) * 1000);
            $status = $resp->status();
            $ok = $resp->successful() || ($status >= 200 && $status < 600);
            $msg = $ok
                ? "Terhubung ke {$url} (status {$status}, {$ms} ms). Kredensial tidak divalidasi."
                : "Gagal HTTP ke {$url} (status {$status}).";

            return redirect()->back()->with('status', $msg);
        } catch (\Throwable $e) {
            return redirect()->back()->with('status', 'Gagal menghubungi '.$url.' : '.$e->getMessage());
        }
    }

    public function advancedTest(Request $request)
    {
        $payload = $request->validate([
            'kode_koperasi' => 'required|string',
        ]);
        $row = DB::table('doku_settings')->where('kode_koperasi', $payload['kode_koperasi'])->first();
        if (! $row) {
            return redirect()->back()->with('status', 'Data DOKU untuk koperasi '.$payload['kode_koperasi'].' belum ditemukan');
        }
        $pubOk = false;
        try {
            $res = @openssl_pkey_get_public($row->public_key);
            if ($res) {
                $pubOk = true;
                @openssl_free_key($res);
            }
        } catch (\Throwable $e) {
            $pubOk = false;
        }
        $url = rtrim((string) $row->base_url, '/').'/';
        $path = '/';
        $requestId = (string) \Illuminate\Support\Str::uuid();
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $body = '';
        $digest = 'SHA-256='.base64_encode(hash('sha256', $body, true));
        $stringToSign = "Client-Id:{$row->client_id}\nRequest-Id:{$requestId}\nRequest-Timestamp:{$timestamp}\nRequest-Target:{$path}\nDigest:{$digest}";
        $sig = base64_encode(hash_hmac('sha256', $stringToSign, $row->secret_key, true));
        $sigHeader = 'HMACSHA256='.$sig;
        try {
            $start = microtime(true);
            $resp = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Komera-DOKU-AdvancedTest/1.0',
                    'Client-Id' => $row->client_id,
                    'Request-Id' => $requestId,
                    'Request-Timestamp' => $timestamp,
                    'Digest' => $digest,
                    'Signature' => $sigHeader,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Channel-Id' => 'WEB',
                    'Authorization' => 'Bearer '.$row->api_key,
                ])
                ->get($url);
            $ms = (int) round((microtime(true) - $start) * 1000);
            $status = $resp->status();
            $ok = $status >= 200 && $status < 500;
            $msg = ($ok ? 'Tersambung' : 'Gagal')." ke {$url} (status {$status}, {$ms} ms). PublicKey=".($pubOk ? 'OK' : 'INVALID').". Signature: {$sigHeader}";

            return redirect()->back()->with('status', $msg);
        } catch (\Throwable $e) {
            $msg = 'Gagal koneksi: '.$e->getMessage().'. PublicKey='.($pubOk ? 'OK' : 'INVALID').". Signature: {$sigHeader}";

            return redirect()->back()->with('status', $msg);
        }
    }

    public function edit($id)
    {
        return redirect()->route('setup-gateway.index');
    }

    public function update(Request $request, $id)
    {
        return $this->store($request);
    }

    public function destroy($id)
    {
        return redirect()->route('setup-gateway.index');
    }

    public function show($id)
    {
        return redirect()->route('setup-gateway.index');
    }
}
