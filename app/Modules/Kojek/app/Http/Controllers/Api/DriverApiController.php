<?php

namespace Modules\Kojek\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Services\FcmService;
use App\Services\OneSignalService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;

class DriverApiController extends Controller
{
    public function register(Request $request)
    {
        $v = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6'],
            'vehicleType' => ['nullable', 'string', 'max:20'],
            'plateNumber' => ['nullable', 'string', 'max:20'],
            'simNumber' => ['nullable', 'string', 'max:50'],
        ]);
        $kopId = (int) $request->attributes->get('koperasi_id');
        $exists = DB::table('driver')->where('koperasi_id', $kopId)->where('email', strtolower(trim($v['email'])))->exists();
        if ($exists) {
            throw ValidationException::withMessages(['email' => 'Email sudah terdaftar']);
        }
        DB::table('driver')->insert([
            'koperasi_id' => $kopId,
            'nama_driver' => $v['name'],
            'email' => strtolower(trim($v['email'])),
            'telepon' => $v['phone'] ?? null,
            'password' => Hash::make($v['password']),
            'jenis_kendaraan' => $v['vehicleType'] ?? null,
            'plat_nomor' => $v['plateNumber'] ?? null,
            'nomor_sim' => $v['simNumber'] ?? null,
            'terverifikasi' => false,
            'status_online' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Pendaftaran diterima, menunggu approval']);
    }

    public function login(Request $request)
    {
        $v = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $kopId = (int) $request->attributes->get('koperasi_id');
        $driver = Driver::query()
            ->where('koperasi_id', $kopId)
            ->where('email', strtolower(trim($v['email'])))
            ->first();
        if (! $driver || ! Hash::check($v['password'], $driver->password)) {
            throw ValidationException::withMessages(['email' => 'Email atau password salah']);
        }
        if (! $driver->terverifikasi) {
            return response()->json(['message' => 'Akun belum diverifikasi'], 403);
        }
        $token = $driver->createToken('kojek')->plainTextToken;

        return response()->json([
            'token' => $token,
            'driver' => [
                'id' => (string) $driver->id,
                'name' => $driver->nama_driver,
                'email' => $driver->email,
            ],
        ]);
    }

    public function setOnline(Request $request)
    {
        $user = $request->user();
        if (! $user || ! ($user instanceof Driver)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $v = $request->validate(['online' => ['required', 'boolean']]);
        DB::table('driver')->where('id', $user->id)->update([
            'status_online' => (bool) $v['online'],
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'OK']);
    }

    public function updateLocation(Request $request)
    {
        $user = $request->user();
        if (! $user || ! ($user instanceof Driver)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $v = $request->validate([
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
        ]);
        DB::table('driver')->where('id', $user->id)->update([
            'latitude_terakhir' => (float) $v['lat'],
            'longitude_terakhir' => (float) $v['lng'],
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'OK']);
    }

    public function availableOrders(Request $request)
    {
        $user = $request->user();
        if (! $user || ! ($user instanceof Driver)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        // Sementara: tampilkan order meskipun driver belum toggle online,
        // untuk memverifikasi konektivitas KOJEK ↔ KoFood tanpa syarat lain.
        $kopId = (int) $request->attributes->get('koperasi_id');
        $rows = DB::table('pesanan_makanan')
            ->join('merchant', 'pesanan_makanan.merchant_id', '=', 'merchant.id')
            ->where('pesanan_makanan.koperasi_id', $kopId)
            ->whereNull('pesanan_makanan.driver_id')
            ->where('pesanan_makanan.tipe_pengiriman', 'delivery')
            ->whereIn('pesanan_makanan.status', ['baru', 'diproses'])
            ->orderByDesc('pesanan_makanan.id')
            ->select(
                'pesanan_makanan.id',
                'pesanan_makanan.nomor_pesanan',
                'pesanan_makanan.alamat_tujuan',
                'pesanan_makanan.latitude_tujuan',
                'pesanan_makanan.longitude_tujuan',
                'pesanan_makanan.biaya_pengiriman',
                'pesanan_makanan.total_bayar',
                'merchant.nama_toko',
                'merchant.banner as merchant_banner',
                'merchant.latitude',
                'merchant.longitude'
            )
            ->get();
        $ids = $rows->pluck('id')->map(fn ($v) => (int) $v)->all();
        $firstProducts = [];
        if (! empty($ids)) {
            $detailRows = DB::table('detail_pesanan_makanan')
                ->whereIn('pesanan_makanan_id', $ids)
                ->orderBy('id')
                ->get();
            foreach ($detailRows as $d) {
                $oid = (int) $d->pesanan_makanan_id;
                if (! isset($firstProducts[$oid])) {
                    $firstProducts[$oid] = (int) $d->produk_id;
                }
            }
        }
        $thumbs = [];
        if (! empty($firstProducts)) {
            $prodIds = array_values(array_unique(array_values($firstProducts)));
            $fotos = DB::table('produk_foto')->whereIn('produk_id', $prodIds)->orderBy('urutan')->get()->groupBy('produk_id');
            foreach ($firstProducts as $orderId => $prodId) {
                $url = null;
                if (isset($fotos[$prodId]) && count($fotos[$prodId]) > 0) {
                    $path = trim((string) $fotos[$prodId][0]->url_foto);
                    if ($path !== '') {
                        $url = Str::startsWith($path, ['http://', 'https://'])
                            ? $path
                            : URL::to('/api/v1/kofood/product-image?path='.rawurlencode($path).'&koperasi_id='.$kopId);
                    }
                }
                $thumbs[$orderId] = $url;
            }
        }
        $dLat = (float) (DB::table('driver')->where('id', $user->id)->value('latitude_terakhir') ?? 0);
        $dLng = (float) (DB::table('driver')->where('id', $user->id)->value('longitude_terakhir') ?? 0);
        $haversine = function ($aLat, $aLng, $bLat, $bLng) {
            $earth = 6371.0;
            $dLat = deg2rad($bLat - $aLat);
            $dLng = deg2rad($bLng - $aLng);
            $aa = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($aLat)) * cos(deg2rad($bLat)) * sin($dLng / 2) * sin($dLng / 2);
            $c = 2 * atan2(sqrt($aa), sqrt(1 - $aa));

            return max(0.0, $earth * $c);
        };
        $data = $rows->map(function ($r) use ($dLat, $dLng, $haversine, $thumbs, $kopId, $firstProducts) {
            $dist = $haversine($dLat, $dLng, (float) ($r->latitude ?? 0), (float) ($r->longitude ?? 0));
            $thumb = $thumbs[(int) $r->id] ?? null;
            if ($thumb === null && ! empty($r->merchant_banner)) {
                $path = ltrim((string) $r->merchant_banner, '/');
                $thumb = URL::to('/api/v1/kofood/product-image?path='.rawurlencode($path).'&koperasi_id='.$kopId);
            }
            return [
                'id' => (string) $r->id,
                'number' => $r->nomor_pesanan,
                'pickupLat' => (float) ($r->latitude ?? 0),
                'pickupLng' => (float) ($r->longitude ?? 0),
                'pickupName' => $r->nama_toko ?? null,
                'destLat' => (float) ($r->latitude_tujuan ?? 0),
                'destLng' => (float) ($r->longitude_tujuan ?? 0),
                'destAddress' => (string) ($r->alamat_tujuan ?? ''),
                'total' => (float) ($r->total_bayar ?? 0),
                'deliveryFee' => (float) ($r->biaya_pengiriman ?? 0),
                'thumbnailUrl' => $thumb,
                'distanceKm' => $dist,
                'etaMinutes' => (int) max(3, min(60, round(($dist / 30.0) * 60.0))),
            ];
        })->sortBy('distanceKm')->values();

        return response()->json($data);
    }

    public function orderDetail(Request $request, $id)
    {
        $user = $request->user();
        if (! $user || ! ($user instanceof Driver)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $kopId = (int) $request->attributes->get('koperasi_id');
        $order = DB::table('pesanan_makanan')
            ->where('koperasi_id', $kopId)
            ->where('id', (int) $id)
            ->first();
        if (! $order) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $merchant = DB::table('merchant')->where('id', $order->merchant_id)->first();
        $itemsRows = DB::table('detail_pesanan_makanan')
            ->join('produk_makanan', 'detail_pesanan_makanan.produk_id', '=', 'produk_makanan.id')
            ->where('detail_pesanan_makanan.pesanan_makanan_id', (int) $order->id)
            ->select(
                'detail_pesanan_makanan.produk_id',
                'detail_pesanan_makanan.jumlah',
                'detail_pesanan_makanan.harga_satuan',
                'detail_pesanan_makanan.subtotal',
                'produk_makanan.nama_produk'
            )
            ->get();
        $prodIds = $itemsRows->pluck('produk_id')->map(fn ($v) => (int) $v)->unique()->values()->all();
        $fotos = [];
        if (! empty($prodIds)) {
            $g = DB::table('produk_foto')->whereIn('produk_id', $prodIds)->orderBy('urutan')->get()->groupBy('produk_id');
            foreach ($g as $pid => $rows) {
                if (count($rows) > 0) {
                    $path = trim((string) $rows[0]->url_foto);
                    if ($path !== '') {
                        $fotos[(int) $pid] = Str::startsWith($path, ['http://', 'https://'])
                            ? $path
                            : URL::to('/api/v1/kofood/product-image?path='.rawurlencode($path).'&koperasi_id='.$kopId);
                    }
                }
            }
        }
        $merchantBanner = null;
        if ($merchant && ! empty($merchant->banner)) {
            $path = ltrim($merchant->banner, '/');
            $merchantBanner = URL::to('/api/v1/kofood/product-image?path='.rawurlencode($path).'&koperasi_id='.$kopId);
        }
        $items = $itemsRows->map(function ($r) use ($fotos, $merchantBanner) {
            $pid = (int) $r->produk_id;
            $img = $fotos[$pid] ?? null;
            if ($img === null) {
                $img = $merchantBanner;
            }
            return [
                'productId' => (string) $pid,
                'name' => (string) $r->nama_produk,
                'qty' => (int) $r->jumlah,
                'price' => (float) $r->harga_satuan,
                'subtotal' => (float) $r->subtotal,
                'imageUrl' => $img,
            ];
        });
        return response()->json([
            'id' => (string) $order->id,
            'number' => (string) ($order->nomor_pesanan ?? ''),
            'pickupLat' => (float) ($merchant->latitude ?? 0),
            'pickupLng' => (float) ($merchant->longitude ?? 0),
            'pickupName' => $merchant->nama_toko ?? null,
            'destLat' => (float) ($order->latitude_tujuan ?? 0),
            'destLng' => (float) ($order->longitude_tujuan ?? 0),
            'destAddress' => (string) ($order->alamat_tujuan ?? ''),
            'subtotal' => (float) ($order->subtotal ?? 0),
            'deliveryFee' => (float) ($order->biaya_pengiriman ?? 0),
            'total' => (float) ($order->total_bayar ?? 0),
            'items' => $items,
        ]);
    }

    public function acceptOrder(Request $request, $id)
    {
        $user = $request->user();
        if (! $user || ! ($user instanceof Driver)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $isOnline = (bool) DB::table('driver')->where('id', $user->id)->value('status_online');
        if (! $isOnline) {
            return response()->json(['message' => 'Driver sedang offline'], 409);
        }
        $kopId = (int) $request->attributes->get('koperasi_id');
        $order = DB::table('pesanan_makanan')
            ->where('koperasi_id', $kopId)
            ->where('id', (int) $id)
            ->whereNull('driver_id')
            ->first();
        if (! $order) {
            return response()->json(['message' => 'Order tidak tersedia'], 409);
        }
        $updated = DB::table('pesanan_makanan')
            ->where('id', (int) $order->id)
            ->update([
                'driver_id' => $user->id,
                'status' => 'dikirim',
                'updated_at' => now(),
            ]);
        if ($updated === 0) {
            return response()->json(['message' => 'Order tidak tersedia'], 409);
        }

        // Kirim notifikasi ke anggota (pemesan) dan pemilik merchant
        try {
            $merchant = DB::table('merchant')->where('id', $order->merchant_id)->first();
            $driver = DB::table('driver')->where('id', $user->id)->first();
            // Anggota pemesan
            $anggotaFcm = DB::table('anggota_device_tokens')
                ->where('anggota_id', (int) $order->anggota_id)
                ->where(function ($q) {
                    $q->whereNull('platform')->orWhere('platform', '!=', 'onesignal');
                })
                ->pluck('token')
                ->filter()
                ->unique()
                ->values()
                ->all();
            $anggotaOs = DB::table('anggota_device_tokens')
                ->where('anggota_id', (int) $order->anggota_id)
                ->where('platform', 'onesignal')
                ->pluck('token')
                ->filter()
                ->unique()
                ->values()
                ->all();
            // Pemilik merchant (anggota_id pada merchant)
            $sellerFcm = [];
            $sellerOs = [];
            if ($merchant && ! empty($merchant->anggota_id)) {
                $sellerFcm = DB::table('anggota_device_tokens')
                    ->where('anggota_id', (int) $merchant->anggota_id)
                    ->where(function ($q) {
                        $q->whereNull('platform')->orWhere('platform', '!=', 'onesignal');
                    })
                    ->pluck('token')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
                $sellerOs = DB::table('anggota_device_tokens')
                    ->where('anggota_id', (int) $merchant->anggota_id)
                    ->where('platform', 'onesignal')
                    ->pluck('token')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }
            $fcmTokens = array_values(array_unique(array_merge($anggotaFcm, $sellerFcm)));
            $oneSignalIds = array_values(array_unique(array_merge($anggotaOs, $sellerOs)));
            if (! empty($fcmTokens) || ! empty($oneSignalIds)) {
                $title = 'Driver Menerima Pesanan';
                $body = ($order->nomor_pesanan ? ('Pesanan '.$order->nomor_pesanan.' ') : 'Pesanan ')
                    .'akan diantar oleh '.(($driver->nama_driver ?? 'Driver'));
                $data = [
                    'type' => 'kofood_driver_accepted',
                    'order_id' => (string) $order->id,
                    'number' => (string) ($order->nomor_pesanan ?? ''),
                ];
                if (! empty($fcmTokens)) {
                    (new FcmService)->sendToTokens($fcmTokens, $title, $body, $data);
                }
                if (! empty($oneSignalIds)) {
                    (new OneSignalService)->sendToPlayerIds($oneSignalIds, $title, $body, $data);
                }
            }
        } catch (\Throwable $e) {
            // abaikan error notifikasi
        }

        return response()->json(['message' => 'OK']);
    }

    public function rejectOrder(Request $request, $id)
    {
        $user = $request->user();
        if (! $user || ! ($user instanceof Driver)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        return response()->json(['message' => 'OK']);
    }

    public function myActiveOrder(Request $request)
    {
        $user = $request->user();
        if (! $user || ! ($user instanceof Driver)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $kopId = (int) $request->attributes->get('koperasi_id');
        $r = DB::table('pesanan_makanan')
            ->join('merchant', 'pesanan_makanan.merchant_id', '=', 'merchant.id')
            ->where('pesanan_makanan.koperasi_id', $kopId)
            ->where('pesanan_makanan.driver_id', $user->id)
            ->whereIn('pesanan_makanan.status', ['dikirim'])
            ->orderByDesc('pesanan_makanan.id')
            ->select(
                'pesanan_makanan.id',
                'pesanan_makanan.nomor_pesanan',
                'pesanan_makanan.alamat_tujuan',
                'pesanan_makanan.latitude_tujuan',
                'pesanan_makanan.longitude_tujuan',
                'pesanan_makanan.total_bayar',
                'merchant.nama_toko',
                'merchant.latitude',
                'merchant.longitude'
            )
            ->first();
        if (! $r) {
            return response()->json(null);
        }
        return response()->json([
            'id' => (string) $r->id,
            'number' => $r->nomor_pesanan,
            'pickupLat' => (float) ($r->latitude ?? 0),
            'pickupLng' => (float) ($r->longitude ?? 0),
            'pickupName' => $r->nama_toko ?? null,
            'destLat' => (float) ($r->latitude_tujuan ?? 0),
            'destLng' => (float) ($r->longitude_tujuan ?? 0),
            'destAddress' => (string) ($r->alamat_tujuan ?? ''),
            'total' => (float) ($r->total_bayar ?? 0),
        ]);
    }

    public function completeOrder(Request $request, $id)
    {
        $user = $request->user();
        if (! $user || ! ($user instanceof Driver)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $kopId = (int) $request->attributes->get('koperasi_id');
        $order = DB::table('pesanan_makanan')
            ->where('koperasi_id', $kopId)
            ->where('id', (int) $id)
            ->where('driver_id', $user->id)
            ->whereIn('status', ['dikirim'])
            ->first();
        if (! $order) {
            return response()->json(['message' => 'Order tidak valid'], 409);
        }
        DB::table('pesanan_makanan')->where('id', (int) $id)->update([
            'status' => 'selesai',
            'updated_at' => now(),
        ]);
        if ($order->status_pembayaran === 'authorized' && $order->jenis_pembayaran === 'dompet') {
            $dompet = DB::table('dompet')
                ->where('koperasi_id', (int) $kopId)
                ->where('anggota_id', (int) $order->anggota_id)
                ->first();
            if ($dompet) {
                $newSaldo = max(0, (int) $dompet->saldo - (int) round($order->total_bayar));
                DB::table('dompet')
                    ->where('id', (int) $dompet->id)
                    ->update(['saldo' => $newSaldo]);
                DB::table('transaksi_dompet')->insert([
                    'koperasi_id' => (int) $kopId,
                    'dompet_id' => (int) $dompet->id,
                    'jenis' => 'PAYMENT_FOOD',
                    'jumlah' => (int) round($order->total_bayar),
                    'referensi_tipe' => 'pesanan_makanan',
                    'referensi_id' => (int) $order->id,
                    'keterangan' => 'Capture pembayaran pesanan',
                    'created_at' => now(),
                ]);
            }
            DB::table('pesanan_makanan')->where('id', (int) $id)->update([
                'status_pembayaran' => 'captured',
                'updated_at' => now(),
            ]);
        }

        return response()->json(['message' => 'OK']);
    }

    public function history(Request $request)
    {
        $user = $request->user();
        if (! $user || ! ($user instanceof Driver)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $kopId = (int) $request->attributes->get('koperasi_id');
        $rows = DB::table('pesanan_makanan')
            ->where('koperasi_id', $kopId)
            ->where('driver_id', $user->id)
            ->whereIn('status', ['selesai', 'dibatalkan', 'gagal'])
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => (string) $r->id,
                    'number' => $r->nomor_pesanan,
                    'destAddress' => (string) ($r->alamat_tujuan ?? ''),
                    'total' => (float) ($r->total_bayar ?? 0),
                    'status' => (string) ($r->status ?? ''),
                    'finishedAt' => (string) ($r->updated_at ?? ''),
                ];
            });

        return response()->json($rows);
    }
}
