<?php

namespace App\Http\Controllers\Api;

use App\Models\Anggota;
use App\Models\Merchant;
use App\Services\DokuClient;
use App\Services\FcmService;
use App\Services\FoodOrderService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class KoFoodController extends BaseController
{
    public function productImage(Request $request)
    {
        $path = ltrim((string) $request->query('path', ''), '/');
        if ($path === '') {
            return response()->json(['message' => 'Invalid path'], 400);
        }
        if (Str::startsWith($path, 'storage/')) {
            $path = substr($path, 8);
        }
        if (! Str::contains($path, '/')) {
            $candidate = 'produk/'.$path;
            if (Storage::disk('public')->exists($candidate)) {
                $path = $candidate;
            }
        }
        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        $stream = $disk->readStream($path);
        if (! $stream) {
            return response()->json(['message' => 'Cannot read file'], 500);
        }

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    public function paymentCallback(Request $request)
    {
        $raw = (string) $request->getContent();
        $data = [];
        try {
            $data = json_decode($raw, true) ?: [];
        } catch (\Throwable $e) {
            $data = [];
        }
        $invoice = (string) ($data['order']['invoice_number'] ?? $data['order']['invoiceNumber'] ?? '');
        if ($invoice === '') {
            return response()->json(['message' => 'invoice_number missing'], 400);
        }
        $status = strtoupper((string) ($data['transaction']['status'] ?? ''));
        $paid = $status === 'SUCCESS';
        $order = DB::table('pesanan_makanan')->where('nomor_pesanan', $invoice)->first();
        if ($order) {
            if ($paid) {
                DB::table('pesanan_makanan')->where('id', $order->id)->update([
                    'status_pembayaran' => 'paid',
                    'status' => $order->status === 'baru' ? 'diproses' : $order->status,
                    'updated_at' => now(),
                ]);
            }
            $trx = DB::table('transaksi_gateway')->where('tipe_transaksi', 'KOFOOD_ORDER')->where('nomor_invoice', $invoice)->first();
            if ($trx) {
                DB::table('transaksi_gateway')->where('id', $trx->id)->update([
                    'status' => $paid ? 'PAID' : ($status ?: 'PENDING'),
                    'response_payload' => json_encode(['callback' => $data]),
                    'updated_at' => now(),
                ]);
            }
        }

        return response()->json(['ok' => true]);
    }

    public function createOrder(Request $request, FoodOrderService $orders, DokuClient $doku)
    {
        $user = $request->user();
        $v = $request->validate([
            'merchant_id' => ['required', 'integer', 'exists:merchant,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:produk_makanan,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'payment' => ['required', 'string', 'in:cod,dompet,pg,pg_va,pg_qris'],
            'payment_sub' => ['nullable', 'string'],
            'alamat_tujuan' => ['required', 'string'],
            'latitude_tujuan' => ['required', 'numeric', 'between:-90,90'],
            'longitude_tujuan' => ['required', 'numeric', 'between:-180,180'],
            'catatan_alamat' => ['nullable', 'string'],
            'buyer_name' => ['nullable', 'string', 'max:150'],
            'buyer_email' => ['nullable', 'email'],
            'buyer_phone' => ['nullable', 'string', 'max:20'],
        ]);
        $kopId = (int) $request->attributes->get('koperasi_id');
        $merchantId = (int) $v['merchant_id'];
        $productIds = array_map(fn ($i) => (int) $i['product_id'], $v['items']);
        $rows = DB::table('produk_makanan')->whereIn('id', $productIds)->get();
        if ($rows->isEmpty()) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 422);
        }
        $validMerchant = $rows->every(fn ($r) => (int) $r->merchant_id === $merchantId);
        if (! $validMerchant) {
            return response()->json(['message' => 'Semua produk harus dari merchant yang sama'], 422);
        }
        $mapHarga = [];
        foreach ($rows as $r) {
            $mapHarga[(int) $r->id] = (float) $r->harga;
        }
        $subtotal = 0.0;
        foreach ($v['items'] as $it) {
            $pid = (int) $it['product_id'];
            $qty = (int) $it['qty'];
            $harga = $mapHarga[$pid] ?? 0.0;
            $subtotal += $harga * $qty;
        }
        $destLat = (float) $v['latitude_tujuan'];
        $destLng = (float) $v['longitude_tujuan'];
        $merchantRow = DB::table('merchant')->where('id', $merchantId)->first();
        $jarakKm = 3.0;
        if ($merchantRow && isset($merchantRow->latitude, $merchantRow->longitude)) {
            $lat1 = (float) $merchantRow->latitude;
            $lng1 = (float) $merchantRow->longitude;
            $earthRadius = 6371.0;
            $dLat = deg2rad($destLat - $lat1);
            $dLng = deg2rad($destLng - $lng1);
            $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($destLat)) * sin($dLng / 2) * sin($dLng / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $jarakKm = max(0.0, $earthRadius * $c);
        }
        $ongkir = $orders->calculateDeliveryFee($jarakKm, $kopId, $merchantId);
        $biayaPlatform = 0.0;
        $total = $subtotal + $ongkir + $biayaPlatform;
        $nomor = 'ORD'.date('ymdHis').Str::upper(Str::random(3));
        $payMethod = $v['payment'];
        if ($payMethod === 'pg' || $payMethod === 'pg_checkout') {
            $payMethod = 'pg_va';
        }
        $anggotaId = null;
        $anggotaStatus = null;
        $anggotaProfile = null;
        if ($user instanceof Anggota) {
            $anggotaId = (int) $user->id;
            $anggotaStatus = (string) ($user->status ?? null);
            $anggotaProfile = [
                'id' => (int) $user->id,
                'nama' => (string) ($user->nama_anggota ?? ''),
                'email' => (string) ($user->email ?? ''),
                'telepon' => (string) ($user->telepon ?? ''),
            ];
        } else {
            $buyerName = (string) ($v['buyer_name'] ?? ($user->name ?? ''));
            $buyerEmail = strtolower(trim((string) ($v['buyer_email'] ?? ($user->email ?? ''))));
            $buyerPhone = (string) ($v['buyer_phone'] ?? ($user->telepon ?? ''));
            if ($buyerEmail === '') {
                $buyerEmail = 'guest+'.Str::lower(Str::random(6)).'@example.local';
            }
            $anggotaRow = DB::table('anggota')
                ->select('id', 'nama_anggota', 'email', 'telepon', 'status')
                ->where('koperasi_id', $kopId)
                ->where('email', $buyerEmail)
                ->first();
            if ($anggotaRow) {
                $anggotaId = (int) $anggotaRow->id;
                $anggotaStatus = (string) ($anggotaRow->status ?? null);
                $anggotaProfile = [
                    'id' => (int) $anggotaRow->id,
                    'nama' => (string) ($anggotaRow->nama_anggota ?? ''),
                    'email' => (string) ($anggotaRow->email ?? ''),
                    'telepon' => (string) ($anggotaRow->telepon ?? ''),
                ];
            } else {
                $newId = DB::table('anggota')->insertGetId([
                    'koperasi_id' => (int) $kopId,
                    'nomor_anggota' => 'G'.substr(md5($buyerEmail.microtime(true)), 0, 8),
                    'nama_anggota' => $buyerName !== '' ? $buyerName : 'Guest',
                    'email' => $buyerEmail,
                    'telepon' => $buyerPhone,
                    'password' => null,
                    'login_google_id' => null,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $anggotaId = (int) $newId;
                $anggotaStatus = 'pending';
                $anggotaProfile = [
                    'id' => (int) $newId,
                    'nama' => $buyerName !== '' ? $buyerName : 'Guest',
                    'email' => $buyerEmail,
                    'telepon' => $buyerPhone,
                ];
            }
        }
        if ($payMethod === 'dompet' && $anggotaStatus !== 'aktif') {
            return response()->json(['message' => 'Pembayaran Dompet hanya untuk anggota aktif'], 422);
        }
        $orderData = [
            'koperasi_id' => $kopId,
            'nomor_pesanan' => $nomor,
            'anggota_id' => (int) $anggotaId,
            'merchant_id' => $merchantId,
            'tipe_pengiriman' => 'delivery',
            'subtotal' => $subtotal,
            'biaya_pengiriman' => $ongkir,
            'biaya_platform' => $biayaPlatform,
            'total_bayar' => $total,
            'jenis_pembayaran' => $payMethod,
            'status_pembayaran' => in_array($payMethod, ['pg_va', 'pg_qris']) ? 'pending' : ($payMethod === 'dompet' ? 'authorized' : 'cod'),
            'referensi_pembayaran' => in_array($payMethod, ['pg_va', 'pg_qris']) ? ('PG-'.Str::upper(Str::random(8))) : null,
            'status' => 'baru',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('pesanan_makanan', 'alamat_tujuan')) {
            $orderData['alamat_tujuan'] = (string) $v['alamat_tujuan'];
        }
        if (Schema::hasColumn('pesanan_makanan', 'latitude_tujuan')) {
            $orderData['latitude_tujuan'] = $destLat;
        }
        if (Schema::hasColumn('pesanan_makanan', 'longitude_tujuan')) {
            $orderData['longitude_tujuan'] = $destLng;
        }
        if (Schema::hasColumn('pesanan_makanan', 'catatan_alamat') && isset($v['catatan_alamat'])) {
            $orderData['catatan_alamat'] = (string) $v['catatan_alamat'];
        }
        $orderId = DB::table('pesanan_makanan')->insertGetId($orderData);
        foreach ($v['items'] as $it) {
            $pid = (int) $it['product_id'];
            $qty = (int) $it['qty'];
            $harga = $mapHarga[$pid] ?? 0.0;
            DB::table('detail_pesanan_makanan')->insert([
                'pesanan_makanan_id' => $orderId,
                'produk_id' => $pid,
                'jumlah' => $qty,
                'harga_satuan' => $harga,
                'subtotal' => $harga * $qty,
            ]);
        }
        // Notify seller (merchant owner)
        try {
            $merchantOwnerId = ($merchantRow && isset($merchantRow->anggota_id)) ? (int) $merchantRow->anggota_id : null;
            if (! empty($merchantOwnerId)) {
                // FCM tokens
                $fcmTokens = DB::table('anggota_device_tokens')
                    ->where('anggota_id', $merchantOwnerId)
                    ->where(function ($q) {
                        $q->whereNull('platform')->orWhere('platform', '!=', 'onesignal');
                    })
                    ->pluck('token')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
                // OneSignal Player IDs
                $oneSignalIds = DB::table('anggota_device_tokens')
                    ->where('anggota_id', $merchantOwnerId)
                    ->where('platform', 'onesignal')
                    ->pluck('token')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
                if (! empty($fcmTokens) || ! empty($oneSignalIds)) {
                    $title = 'Pesanan Baru';
                    $totalText = number_format((float) $total, 0, ',', '.');
                    $body = $nomor.' • Total Rp '.$totalText;
                    $data = [
                        'type' => 'kofood_order_new',
                        'order_id' => (string) $orderId,
                        'number' => (string) $nomor,
                    ];
                    if (! empty($fcmTokens)) {
                        (new FcmService)->sendToTokens($fcmTokens, $title, $body, $data);
                    }
                    if (! empty($oneSignalIds)) {
                        (new \App\Services\OneSignalService)->sendToPlayerIds($oneSignalIds, $title, $body, $data);
                    }
                }
            }
        } catch (\Throwable $e) {
            // silently ignore notification errors
        }
        $payUrl = null;
        if (in_array($payMethod, ['pg_va', 'pg_qris', 'pg_checkout'])) {
            if ($payMethod === 'pg_va') {
                $checkout = null;
                try {
                    $sub = strtoupper((string) $request->input('payment_sub', 'DOKU'));
                    $checkout = $doku->createCheckoutPayment(
                        (string) $kopId,
                        (string) $nomor,
                        (int) round($total),
                        $anggotaProfile,
                        'VA',
                        $sub
                    );
                } catch (\Throwable $e) {
                    $checkout = ['success' => false, 'error_message' => $e->getMessage()];
                }
                if (is_array($checkout) && ($checkout['success'] ?? false)) {
                    $gwId = DB::table('setup_gateway')
                        ->where('koperasi_id', (int) $kopId)
                        ->where('status_aktif', true)
                        ->orderByDesc('id')
                        ->value('id');
                    if ($gwId) {
                        try {
                            DB::table('transaksi_gateway')->insert([
                                'koperasi_id' => (int) $kopId,
                                'gateway_id' => (int) $gwId,
                                'tipe_transaksi' => 'KOFOOD_ORDER',
                                'referensi_id' => (int) $orderId,
                                'nomor_invoice' => $nomor,
                                'external_id' => null,
                                'jumlah' => (int) round($total),
                                'response_payload' => json_encode($checkout['raw'] ?? []),
                                'status' => 'PENDING',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } catch (\Throwable $e) {
                        }
                    }
                    DB::table('pesanan_makanan')->where('id', $orderId)->update([
                        'referensi_pembayaran' => 'CHK:DOKU',
                        'updated_at' => now(),
                    ]);
                    $payUrl = (string) ($checkout['payment_url'] ?? '');
                }
                if (! $payUrl) {
                    $envName = $doku->getEnvName((string) $kopId) ?: 'unknown';
                    $detail = is_array($checkout) ? ($checkout['status'] ?? '').': '.($checkout['error_message'] ?? '') : null;
                    return response()->json([
                        'message' => 'Payment Gateway DOKU ('.$envName.') tidak mengembalikan URL pembayaran.',
                        'detail' => trim((string) $detail),
                    ], 422);
                }
            } elseif ($payMethod === 'pg_qris') {
                $checkout = null;
                try {
                    $checkout = $doku->createCheckoutPayment(
                        (string) $kopId,
                        (string) $nomor,
                        (int) round($total),
                        $anggotaProfile,
                        'QRIS',
                        null
                    );
                } catch (\Throwable $e) {
                    $checkout = ['success' => false, 'error_message' => $e->getMessage()];
                }
                if (is_array($checkout) && ($checkout['success'] ?? false)) {
                    $gwId = DB::table('setup_gateway')
                        ->where('koperasi_id', (int) $kopId)
                        ->where('status_aktif', true)
                        ->orderByDesc('id')
                        ->value('id');
                    if ($gwId) {
                        try {
                            DB::table('transaksi_gateway')->insert([
                                'koperasi_id' => (int) $kopId,
                                'gateway_id' => (int) $gwId,
                                'tipe_transaksi' => 'KOFOOD_ORDER',
                                'referensi_id' => (int) $orderId,
                                'nomor_invoice' => $nomor,
                                'external_id' => null,
                                'jumlah' => (int) round($total),
                                'response_payload' => json_encode($checkout['raw'] ?? []),
                                'status' => 'PENDING',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } catch (\Throwable $e) {
                        }
                    }
                    DB::table('pesanan_makanan')->where('id', $orderId)->update([
                        'referensi_pembayaran' => 'CHK:DOKU',
                        'updated_at' => now(),
                    ]);
                    $payUrl = (string) ($checkout['payment_url'] ?? '');
                }
                if (! $payUrl) {
                    $envName = $doku->getEnvName((string) $kopId) ?: 'unknown';
                    $detail = is_array($checkout) ? ($checkout['status'] ?? '').': '.($checkout['error_message'] ?? '') : null;
                    return response()->json([
                        'message' => 'Payment Gateway DOKU ('.$envName.') tidak mengembalikan URL pembayaran QRIS.',
                        'detail' => trim((string) $detail),
                    ], 422);
                }
            }
        }

        return response()->json([
            'id' => (int) $orderId,
            'number' => $nomor,
            'payment' => $payMethod,
            'payment_status' => in_array($payMethod, ['pg_va', 'pg_qris']) ? 'pending' : ($payMethod === 'dompet' ? 'authorized' : 'cod'),
            'total' => $total,
            'pay_url' => $payUrl,
        ], 201);
    }

    public function sellerOrders(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $kopId = (int) $request->attributes->get('koperasi_id');
        $merchantId = null;
        if ($user instanceof Merchant) {
            $merchantId = (int) $user->id;
        } elseif ($user instanceof Anggota) {
            $m = DB::table('merchant')
                ->where('koperasi_id', $kopId)
                ->where('anggota_id', (int) $user->id)
                ->where('status', 'aktif')
                ->first();
            if ($m) {
                $merchantId = (int) $m->id;
            }
        }
        if (! $merchantId) {
            return response()->json(['message' => 'Hanya seller yang dapat melihat pesanan'], 403);
        }
        $items = DB::table('pesanan_makanan')
            ->where('koperasi_id', $kopId)
            ->where('merchant_id', $merchantId)
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => (int) $r->id,
                    'number' => $r->nomor_pesanan,
                    'status' => $r->status,
                    'payment_status' => $r->status_pembayaran,
                    'total' => (float) $r->total_bayar,
                    'dest' => [
                        'address' => $r->alamat_tujuan ?? null,
                        'lat' => isset($r->latitude_tujuan) ? (float) $r->latitude_tujuan : null,
                        'lng' => isset($r->longitude_tujuan) ? (float) $r->longitude_tujuan : null,
                    ],
                    'created_at' => $r->created_at,
                ];
            });

        return response()->json(['data' => $items]);
    }

    public function processSellerOrder(Request $request, $id)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $kopId = (int) $request->attributes->get('koperasi_id');
        $merchantId = null;
        if ($user instanceof Merchant) {
            $merchantId = (int) $user->id;
        } elseif ($user instanceof Anggota) {
            $m = DB::table('merchant')
                ->where('koperasi_id', $kopId)
                ->where('anggota_id', (int) $user->id)
                ->where('status', 'aktif')
                ->first();
            if ($m) {
                $merchantId = (int) $m->id;
            }
        }
        if (! $merchantId) {
            return response()->json(['message' => 'Hanya seller yang dapat memproses pesanan'], 403);
        }
        $updated = DB::table('pesanan_makanan')
            ->where('koperasi_id', $kopId)
            ->where('merchant_id', $merchantId)
            ->where('id', (int) $id)
            ->where('status', 'baru')
            ->update([
                'status' => 'diproses',
                'updated_at' => now(),
            ]);
        if ($updated === 0) {
            return response()->json(['message' => 'Pesanan tidak ditemukan atau sudah diproses'], 404);
        }

        return response()->json(['message' => 'OK']);
    }

    public function rejectSellerOrder(Request $request, $id)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $kopId = (int) $request->attributes->get('koperasi_id');
        $merchantId = null;
        if ($user instanceof Merchant) {
            $merchantId = (int) $user->id;
        } elseif ($user instanceof Anggota) {
            $m = DB::table('merchant')
                ->where('koperasi_id', $kopId)
                ->where('anggota_id', (int) $user->id)
                ->where('status', 'aktif')
                ->first();
            if ($m) {
                $merchantId = (int) $m->id;
            }
        }
        if (! $merchantId) {
            return response()->json(['message' => 'Hanya seller yang dapat menolak pesanan'], 403);
        }
        $order = DB::table('pesanan_makanan')
            ->where('koperasi_id', $kopId)
            ->where('merchant_id', $merchantId)
            ->where('id', (int) $id)
            ->first();
        if (! $order || ! in_array($order->status, ['baru', 'diproses'], true)) {
            return response()->json(['message' => 'Pesanan tidak valid untuk ditolak'], 409);
        }
        DB::table('pesanan_makanan')
            ->where('id', (int) $id)
            ->update([
                'status' => 'dibatalkan',
                'updated_at' => now(),
                'status_pembayaran' => ($order->status_pembayaran === 'authorized') ? 'void' : $order->status_pembayaran,
            ]);
        if ($order->status_pembayaran === 'authorized' && $order->jenis_pembayaran === 'dompet') {
            $dompetId = DB::table('dompet')->where('koperasi_id', (int) $kopId)->where('anggota_id', (int) $order->anggota_id)->value('id');
            if ($dompetId) {
                DB::table('transaksi_dompet')->insert([
                    'koperasi_id' => (int) $kopId,
                    'dompet_id' => (int) $dompetId,
                    'jenis' => 'HOLD_VOID',
                    'jumlah' => (int) round($order->total_bayar),
                    'referensi_tipe' => 'pesanan_makanan',
                    'referensi_id' => (int) $order->id,
                    'keterangan' => 'Pembatalan pesanan (VOID)',
                    'created_at' => now(),
                ]);
            }
        }

        return response()->json(['message' => 'Order ditolak']);
    }

    public function categories(Request $request)
    {
        $kopId = (int) $request->header('X-Koperasi-Id');
        $rows = DB::table('kategori_produk')
            ->where('koperasi_id', $kopId)
            ->orderBy('nama_kategori')
            ->get()
            ->map(function ($r) use ($kopId) {
                $img = null;
                if (! empty($r->gambar)) {
                    $path = ltrim($r->gambar, '/');
                    $img = URL::to('/api/v1/kofood/product-image?path='.rawurlencode($path).'&koperasi_id='.$kopId);
                }

                return [
                    'id' => (int) $r->id,
                    'name' => $r->nama_kategori,
                    'imageUrl' => $img,
                ];
            });

        return response()->json(['data' => $rows]);
    }

    public function merchants(Request $request)
    {
        $kopId = (int) $request->header('X-Koperasi-Id');
        $lat = $request->query('lat');
        $lng = $request->query('lng');
        $radiusKm = (float) ($request->query('radius_km', 50));
        $rows = DB::table('merchant')
            ->where('koperasi_id', $kopId)
            ->where('status', 'aktif')
            ->get();
        $data = $rows->map(function ($m) use ($lat, $lng, $kopId) {
            $dist = 0.0;
            if (! is_null($lat) && ! is_null($lng) && isset($m->latitude, $m->longitude)) {
                $lat1 = (float) $lat;
                $lng1 = (float) $lng;
                $lat2 = (float) $m->latitude;
                $lng2 = (float) $m->longitude;
                $earth = 6371.0;
                $dLat = deg2rad($lat2 - $lat1);
                $dLng = deg2rad($lng2 - $lng1);
                $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) * sin($dLng / 2);
                $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                $dist = max(0.0, $earth * $c);
            }
            $bannerUrl = null;
            if (! empty($m->banner)) {
                $path = ltrim($m->banner, '/');
                $bannerUrl = URL::to('/api/v1/kofood/product-image?path='.rawurlencode($path).'&koperasi_id='.$kopId);
            } else {
                $firstPhoto = DB::table('produk_makanan')
                    ->join('produk_foto', 'produk_makanan.id', '=', 'produk_foto.produk_id')
                    ->where('produk_makanan.merchant_id', $m->id)
                    ->orderBy('produk_foto.urutan')
                    ->value('produk_foto.url_foto');
                if ($firstPhoto) {
                    $path = trim((string) $firstPhoto);
                    $bannerUrl = Str::startsWith($path, ['http://', 'https://'])
                        ? $path
                        : URL::to('/api/v1/kofood/product-image?path='.rawurlencode($path).'&koperasi_id='.$kopId);
                }
            }

            return [
                'id' => (string) $m->id,
                'name' => $m->nama_toko,
                'bannerUrl' => $bannerUrl,
                'distanceKm' => $dist,
                'rating' => 0,
                'address' => $m->alamat,
            ];
        })->filter(function ($m) use ($radiusKm) {
            return $radiusKm <= 0 ? true : ($m['distanceKm'] <= $radiusKm);
        })->sortBy('distanceKm')->values();

        return response()->json(['data' => $data]);
    }

    public function merchant($id)
    {
        $m = DB::table('merchant')->where('id', $id)->where('status', 'aktif')->first();
        if (! $m) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $bannerUrl = null;
        if (! empty($m->banner)) {
            $path = ltrim($m->banner, '/');
            $bannerUrl = URL::to('/api/v1/kofood/product-image?path='.rawurlencode($path).'&koperasi_id='.(int) request()->header('X-Koperasi-Id'));
        } else {
            $firstPhoto = DB::table('produk_makanan')
                ->join('produk_foto', 'produk_makanan.id', '=', 'produk_foto.produk_id')
                ->where('produk_makanan.merchant_id', $m->id)
                ->orderBy('produk_foto.urutan')
                ->value('produk_foto.url_foto');
            if ($firstPhoto) {
                $path = trim((string) $firstPhoto);
                $bannerUrl = Str::startsWith($path, ['http://', 'https://'])
                    ? $path
                    : URL::to('/api/v1/kofood/product-image?path='.rawurlencode($path).'&koperasi_id='.(int) request()->header('X-Koperasi-Id'));
            }
        }

        return response()->json([
            'data' => [
                'id' => (string) $m->id,
                'name' => $m->nama_toko,
                'bannerUrl' => $bannerUrl,
                'distanceKm' => 0,
                'rating' => 0,
                'address' => $m->alamat,
            ],
        ]);
    }

    public function merchantProducts(Request $request, $merchantId)
    {
        $isActive = DB::table('merchant')->where('id', $merchantId)->where('status', 'aktif')->exists();
        if (! $isActive) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $kopId = (int) $request->header('X-Koperasi-Id');
        $rows = DB::table('produk_makanan')->where('merchant_id', $merchantId)->orderBy('nama_produk')->get();
        $ids = $rows->pluck('id')->all();
        $fotos = DB::table('produk_foto')->whereIn('produk_id', $ids)->orderBy('urutan')->get()->groupBy('produk_id');
        $data = $rows->map(function ($p) use ($fotos) {
            $images = [];
            if (isset($fotos[$p->id])) {
                foreach ($fotos[$p->id] as $f) {
                    $path = trim((string) $f->url_foto);
                    if ($path === '') {
                        continue;
                    }
                    $images[] = Str::startsWith($path, ['http://', 'https://'])
                        ? $path
                        : URL::to('/api/v1/kofood/product-image?path='.rawurlencode($path).'&koperasi_id='.request()->header('X-Koperasi-Id'));
                }
            }

            return [
                'id' => (string) $p->id,
                'merchantId' => (string) $p->merchant_id,
                'name' => $p->nama_produk,
                'description' => $p->deskripsi,
                'price' => (float) $p->harga,
                'images' => $images,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function product(Request $request, $id)
    {
        $p = DB::table('produk_makanan')->where('id', $id)->first();
        if (! $p) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $kopId = (int) $request->header('X-Koperasi-Id');
        $fotos = DB::table('produk_foto')->where('produk_id', $p->id)->orderBy('urutan')->get();
        $images = [];
        foreach ($fotos as $f) {
            $path = trim((string) $f->url_foto);
            if ($path === '') {
                continue;
            }
            $images[] = Str::startsWith($path, ['http://', 'https://'])
                ? $path
                : URL::to('/api/v1/kofood/product-image?path='.rawurlencode($path).'&koperasi_id='.request()->header('X-Koperasi-Id'));
        }

        return response()->json([
            'data' => [
                'id' => (string) $p->id,
                'merchantId' => (string) $p->merchant_id,
                'name' => $p->nama_produk,
                'description' => $p->deskripsi,
                'price' => (float) $p->harga,
                'images' => $images,
            ],
        ]);
    }

    public function myOrders(Request $request)
    {
        $user = $request->user();
        $kopId = (int) $request->attributes->get('koperasi_id');
        $anggotaId = null;
        if ($user instanceof Anggota) {
            $anggotaId = (int) $user->id;
        } else {
            $email = strtolower(trim((string) ($user->email ?? '')));
            if ($email !== '') {
                $row = DB::table('anggota')->where('koperasi_id', $kopId)->where('email', $email)->first();
                if ($row) {
                    $anggotaId = (int) $row->id;
                }
            }
        }
        if (! $anggotaId) {
            return response()->json(['data' => []]);
        }
        $items = DB::table('pesanan_makanan')
            ->where('koperasi_id', $kopId)
            ->where('anggota_id', (int) $anggotaId)
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => (int) $r->id,
                    'number' => $r->nomor_pesanan,
                    'status' => $r->status,
                    'payment_status' => $r->status_pembayaran,
                    'total' => (float) $r->total_bayar,
                    'created_at' => $r->created_at,
                ];
            });

        return response()->json(['data' => $items]);
    }

    public function orderDetail(Request $request, $id)
    {
        $user = $request->user();
        $kopId = (int) $request->attributes->get('koperasi_id');
        $order = DB::table('pesanan_makanan')->where('koperasi_id', $kopId)->where('id', (int) $id)->first();
        if (! $order) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $anggotaId = null;
        if ($user instanceof Anggota) {
            $anggotaId = (int) $user->id;
        } else {
            $email = strtolower(trim((string) ($user->email ?? '')));
            if ($email !== '') {
                $row = DB::table('anggota')->where('koperasi_id', $kopId)->where('email', $email)->first();
                if ($row) {
                    $anggotaId = (int) $row->id;
                }
            }
        }
        if (! $anggotaId || (int) $order->anggota_id !== (int) $anggotaId) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $items = DB::table('detail_pesanan_makanan')->where('pesanan_makanan_id', $order->id)->get()->map(function ($d) {
            return [
                'product_id' => (int) $d->produk_id,
                'qty' => (int) $d->jumlah,
                'price' => (float) $d->harga_satuan,
                'subtotal' => (float) $d->subtotal,
            ];
        });
        $merchant = DB::table('merchant')->where('id', $order->merchant_id)->first();

        return response()->json([
            'data' => [
                'id' => (int) $order->id,
                'number' => $order->nomor_pesanan,
                'status' => $order->status,
                'payment_status' => $order->status_pembayaran,
                'subtotal' => (float) $order->subtotal,
                'delivery_fee' => (float) $order->biaya_pengiriman,
                'platform_fee' => (float) $order->biaya_platform,
                'total' => (float) $order->total_bayar,
                'items' => $items,
                'merchant' => $merchant ? [
                    'id' => (int) $merchant->id,
                    'name' => $merchant->nama_toko,
                    'latitude' => (float) ($merchant->latitude ?? 0),
                    'longitude' => (float) ($merchant->longitude ?? 0),
                ] : null,
                'destination' => [
                    'address' => (string) ($order->alamat_tujuan ?? ''),
                    'latitude' => (float) ($order->latitude_tujuan ?? 0),
                    'longitude' => (float) ($order->longitude_tujuan ?? 0),
                ],
            ],
        ]);
    }

    public function orderTracking(Request $request, $id)
    {
        $user = $request->user();
        $kopId = (int) $request->attributes->get('koperasi_id');
        $order = DB::table('pesanan_makanan')->where('koperasi_id', $kopId)->where('id', (int) $id)->first();
        if (! $order) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $anggotaId = null;
        if ($user instanceof Anggota) {
            $anggotaId = (int) $user->id;
        } else {
            $email = strtolower(trim((string) ($user->email ?? '')));
            if ($email !== '') {
                $row = DB::table('anggota')->where('koperasi_id', $kopId)->where('email', $email)->first();
                if ($row) {
                    $anggotaId = (int) $row->id;
                }
            }
        }
        if (! $anggotaId || (int) $order->anggota_id !== (int) $anggotaId) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $merchant = DB::table('merchant')->where('id', $order->merchant_id)->first();
        $driver = $order->driver_id ? DB::table('driver')->where('id', $order->driver_id)->first() : null;
        $origin = [
            'lat' => (float) ($merchant->latitude ?? 0),
            'lng' => (float) ($merchant->longitude ?? 0),
        ];
        $destination = [
            'lat' => (float) ($order->latitude_tujuan ?? 0),
            'lng' => (float) ($order->longitude_tujuan ?? 0),
        ];
        $driverPos = [
            'lat' => (float) ($driver && isset($driver->latitude_terakhir) ? $driver->latitude_terakhir : $origin['lat']),
            'lng' => (float) ($driver && isset($driver->longitude_terakhir) ? $driver->longitude_terakhir : $origin['lng']),
        ];
        $haversine = function ($aLat, $aLng, $bLat, $bLng) {
            $earth = 6371.0;
            $dLat = deg2rad($bLat - $aLat);
            $dLng = deg2rad($bLng - $aLng);
            $aa = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($aLat)) * cos(deg2rad($bLat)) * sin($dLng / 2) * sin($dLng / 2);
            $c = 2 * atan2(sqrt($aa), sqrt(1 - $aa));

            return max(0.0, $earth * $c);
        };
        $distToDest = $haversine($driverPos['lat'], $driverPos['lng'], $destination['lat'], $destination['lng']);
        $etaMinutes = (int) round(($distToDest / 30.0) * 60.0); // 30 km/h
        $etaMinutes = max(3, min(60, $etaMinutes));

        $timeline = [];
        $timeline[] = [
            'key' => 'baru',
            'label' => 'Pesanan dibuat',
            'time' => (string) $order->created_at,
        ];
        if (in_array($order->status, ['diproses', 'dikirim', 'selesai', 'batal', 'dibatalkan'])) {
            $timeline[] = [
                'key' => 'diproses',
                'label' => 'Pesanan diproses',
                'time' => (string) $order->updated_at,
            ];
        }
        if (in_array($order->status, ['dikirim', 'selesai'])) {
            $timeline[] = [
                'key' => 'dikirim',
                'label' => 'Pesanan diantar',
                'time' => (string) $order->updated_at,
            ];
        }
        if ($order->status === 'selesai') {
            $timeline[] = [
                'key' => 'selesai',
                'label' => 'Pesanan selesai',
                'time' => (string) $order->updated_at,
            ];
        }

        return response()->json([
            'data' => [
                'status' => $order->status,
                'origin' => $origin,
                'destination' => $destination,
                'driver' => [
                    'lat' => $driverPos['lat'],
                    'lng' => $driverPos['lng'],
                    'name' => $driver && isset($driver->nama_driver) ? $driver->nama_driver : 'Driver',
                    'plate' => $driver && isset($driver->plat_nomor) ? $driver->plat_nomor : '',
                ],
                'eta_minutes' => $etaMinutes,
                'updated_at' => now()->toIso8601String(),
                'timeline' => $timeline,
            ],
        ]);
    }

    public function search(Request $request)
    {
        $kopId = (int) ($request->attributes->get('koperasi_id') ?? $request->header('X-Koperasi-Id'));
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json(['data' => []]);
        }
        $like = '%'.strtolower($q).'%';
        $merchants = DB::table('merchant')
            ->where('koperasi_id', $kopId)
            ->where('status', 'aktif')
            ->whereRaw('LOWER(nama_toko) LIKE ?', [$like])
            ->limit(20)
            ->get()
            ->map(function ($m) {
                return [
                    'type' => 'merchant',
                    'id' => (string) $m->id,
                    'title' => $m->nama_toko,
                    'subtitle' => $m->alamat,
                    'image' => null,
                ];
            })
            ->all();
        $products = DB::table('produk_makanan')
            ->join('merchant', 'produk_makanan.merchant_id', '=', 'merchant.id')
            ->where('merchant.koperasi_id', $kopId)
            ->where('merchant.status', 'aktif')
            ->whereRaw('LOWER(produk_makanan.nama_produk) LIKE ?', [$like])
            ->select('produk_makanan.*', 'merchant.nama_toko')
            ->limit(30)
            ->get();
        $prodIds = $products->pluck('id')->all();
        $fotos = DB::table('produk_foto')->whereIn('produk_id', $prodIds)->orderBy('urutan')->get()->groupBy('produk_id');
        $productItems = $products->map(function ($p) use ($fotos) {
            $img = null;
            if (isset($fotos[$p->id])) {
                foreach ($fotos[$p->id] as $f) {
                    $path = trim((string) $f->url_foto);
                    if ($path === '') {
                        continue;
                    }
                    $img = Str::startsWith($path, ['http://', 'https://'])
                        ? $path
                        : url('/api/v1/kofood/product-image?path='.rawurlencode($path));
                    break;
                }
            }

            return [
                'type' => 'product',
                'id' => (string) $p->id,
                'title' => $p->nama_produk,
                'subtitle' => $p->nama_toko ?? '',
                'image' => $img,
            ];
        })->all();
        $data = array_values(array_merge($merchants, $productItems));

        return response()->json(['data' => $data]);
    }
}
