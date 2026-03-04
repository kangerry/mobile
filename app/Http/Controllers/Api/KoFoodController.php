<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Anggota;
use App\Services\FoodOrderService;
use App\Services\DokuClient;

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
        if (! Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $full = Storage::disk('public')->path($path);
        return response()->file($full)->header('Access-Control-Allow-Origin', '*');
    }

    public function createOrder(Request $request, FoodOrderService $orders, DokuClient $doku)
    {
        $user = $request->user();
        if (! $user || ! ($user instanceof Anggota)) {
            return response()->json(['message' => 'Hanya anggota yang dapat membuat pesanan'], 403);
        }
        $v = $request->validate([
            'merchant_id' => ['required', 'integer', 'exists:merchant,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:produk_makanan,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'payment' => ['required', 'string', 'in:cod,dompet,pg'],
            'alamat_tujuan' => ['required', 'string'],
            'latitude_tujuan' => ['required', 'numeric', 'between:-90,90'],
            'longitude_tujuan' => ['required', 'numeric', 'between:-180,180'],
            'catatan_alamat' => ['nullable', 'string'],
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
        $orderData = [
            'koperasi_id' => $kopId,
            'nomor_pesanan' => $nomor,
            'anggota_id' => (int) $user->id,
            'merchant_id' => $merchantId,
            'tipe_pengiriman' => 'delivery',
            'subtotal' => $subtotal,
            'biaya_pengiriman' => $ongkir,
            'biaya_platform' => $biayaPlatform,
            'total_bayar' => $total,
            'jenis_pembayaran' => $v['payment'],
            'status_pembayaran' => $v['payment'] === 'pg' ? 'pending' : ($v['payment'] === 'dompet' ? 'paid' : 'cod'),
            'referensi_pembayaran' => $v['payment'] === 'pg' ? ('PG-'.Str::upper(Str::random(8))) : null,
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
        $payUrl = null;
        if ($v['payment'] === 'pg') {
            $anggotaProfile = [
                'id' => (int) $user->id,
                'nama' => (string) ($user->nama_anggota ?? ''),
                'email' => (string) ($user->email ?? ''),
                'telepon' => (string) ($user->telepon ?? ''),
            ];
            $va = null;
            try {
                $va = $doku->createTopupVa((string) $kopId, $anggotaProfile, (int) round($total), 'VIRTUAL_ACCOUNT_BRI');
            } catch (\Throwable $e) {
                $va = null;
            }
            if ($va && isset($va['virtual_account_no'])) {
                DB::table('transaksi_gateway')->insert([
                    'koperasi_id' => (int) $kopId,
                    'gateway_id' => 0,
                    'tipe_transaksi' => 'KOFOOD_ORDER',
                    'referensi_id' => (int) $orderId,
                    'nomor_invoice' => $nomor,
                    'external_id' => $va['virtual_account_no'],
                    'jumlah' => (int) round($total),
                    'response_payload' => json_encode($va),
                    'status' => 'PENDING',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('pesanan_makanan')->where('id', $orderId)->update([
                    'referensi_pembayaran' => 'VA:'.$va['virtual_account_no'],
                    'updated_at' => now(),
                ]);
                $payUrl = $va['how_to_pay_page'] ?? null;
            }
            if (! $payUrl) {
                $payUrl = URL::to('/pg/simulated-checkout?order='.$nomor);
            }
        }

        return response()->json([
            'id' => (int) $orderId,
            'number' => $nomor,
            'payment' => $v['payment'],
            'payment_status' => $v['payment'] === 'pg' ? 'pending' : ($v['payment'] === 'dompet' ? 'paid' : 'cod'),
            'total' => $total,
            'pay_url' => $payUrl,
        ], 201);
    }

    public function categories(Request $request)
    {
        $kopId = (int) $request->header('X-Koperasi-Id');
        $rows = DB::table('kategori_produk')
            ->where('koperasi_id', $kopId)
            ->orderBy('nama_kategori')
            ->get()
            ->map(function ($r) {
                return [
                    'id' => (int) $r->id,
                    'name' => $r->nama_kategori,
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
        $data = $rows->map(function ($m) use ($lat, $lng) {
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
            return [
                'id' => (string) $m->id,
                'name' => $m->nama_toko,
                'bannerUrl' => null,
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

        return response()->json([
            'data' => [
                'id' => (string) $m->id,
                'name' => $m->nama_toko,
                'bannerUrl' => null,
                'distanceKm' => 0,
                'rating' => 0,
                'address' => $m->alamat,
            ],
        ]);
    }

    public function merchantProducts($merchantId)
    {
        $isActive = DB::table('merchant')->where('id', $merchantId)->where('status', 'aktif')->exists();
        if (! $isActive) {
            return response()->json(['message' => 'Not found'], 404);
        }
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
                        : URL::to('/api/v1/kofood/product-image?path='.rawurlencode($path));
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

    public function product($id)
    {
        $p = DB::table('produk_makanan')->where('id', $id)->first();
        if (! $p) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $fotos = DB::table('produk_foto')->where('produk_id', $p->id)->orderBy('urutan')->get();
        $images = [];
        foreach ($fotos as $f) {
            $path = trim((string) $f->url_foto);
            if ($path === '') {
                continue;
            }
            $images[] = Str::startsWith($path, ['http://', 'https://'])
                ? $path
                : URL::to('/api/v1/kofood/product-image?path='.rawurlencode($path));
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
        if (! $user || ! ($user instanceof Anggota)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $kopId = (int) $request->attributes->get('koperasi_id');
        $items = DB::table('pesanan_makanan')
            ->where('koperasi_id', $kopId)
            ->where('anggota_id', (int) $user->id)
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
        if (! $user || ! ($user instanceof Anggota)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $kopId = (int) $request->attributes->get('koperasi_id');
        $order = DB::table('pesanan_makanan')->where('koperasi_id', $kopId)->where('id', (int) $id)->first();
        if (! $order || (int) $order->anggota_id !== (int) $user->id) {
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
        if (! $user || ! ($user instanceof Anggota)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $kopId = (int) $request->attributes->get('koperasi_id');
        $order = DB::table('pesanan_makanan')->where('koperasi_id', $kopId)->where('id', (int) $id)->first();
        if (! $order || (int) $order->anggota_id !== (int) $user->id) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $merchant = DB::table('merchant')->where('id', $order->merchant_id)->first();
        $driver = null;
        if ($order->driver_id) {
            $driver = DB::table('driver')->where('id', $order->driver_id)->first();
        }
        $origin = [
            'lat' => (float) ($merchant->latitude ?? 0),
            'lng' => (float) ($merchant->longitude ?? 0),
        ];
        $destination = [
            'lat' => (float) ($order->latitude_tujuan ?? 0),
            'lng' => (float) ($order->longitude_tujuan ?? 0),
        ];
        $driverPos = [
            'lat' => (float) ($driver->latitude_terakhir ?? $origin['lat']),
            'lng' => (float) ($driver->longitude_terakhir ?? $origin['lng']),
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
                    'name' => $driver->nama_driver ?? 'Driver',
                    'plate' => $driver->plat_nomor ?? '',
                ],
                'eta_minutes' => $etaMinutes,
                'updated_at' => now()->toIso8601String(),
                'timeline' => $timeline,
            ],
        ]);
    }
}
