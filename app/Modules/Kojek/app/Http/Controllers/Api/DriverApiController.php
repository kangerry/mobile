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
        $kopId = (int) $request->attributes->get('koperasi_id');
        $rows = DB::table('pesanan_makanan')
            ->join('merchant', 'pesanan_makanan.merchant_id', '=', 'merchant.id')
            ->where('pesanan_makanan.koperasi_id', $kopId)
            ->whereNull('pesanan_makanan.driver_id')
            ->where('pesanan_makanan.tipe_pengiriman', 'delivery')
            ->whereIn('pesanan_makanan.status', ['diproses'])
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
            ->get();
        $data = $rows->map(function ($r) {
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
            ];
        });

        return response()->json($data->values());
    }

    public function acceptOrder(Request $request, $id)
    {
        $user = $request->user();
        if (! $user || ! ($user instanceof Driver)) {
            return response()->json(['message' => 'Unauthorized'], 401);
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
        $updated = DB::table('pesanan_makanan')
            ->where('koperasi_id', $kopId)
            ->where('id', (int) $id)
            ->where('driver_id', $user->id)
            ->whereIn('status', ['dikirim'])
            ->update([
                'status' => 'selesai',
                'updated_at' => now(),
            ]);
        if ($updated === 0) {
            return response()->json(['message' => 'Order tidak valid'], 409);
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
