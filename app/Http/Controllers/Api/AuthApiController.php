<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    private function resolveKoperasiId(Request $request): int
    {
        $id = (int) ($request->attributes->get('koperasi_id') ?? $request->header('X-Koperasi-Id') ?? $request->input('koperasi_id') ?? 1);
        $exists = DB::table('koperasi')->where('id', $id)->exists();
        if ($exists) {
            return $id;
        }
        $first = DB::table('koperasi')->orderBy('id')->first();
        if ($first) {
            return (int) $first->id;
        }
        return (int) DB::table('koperasi')->insertGetId([
            'kode_koperasi' => 'KOP001',
            'nama_koperasi' => 'Koperasi Default',
            'logo' => null,
            'alamat' => 'Alamat',
            'provinsi' => 'Provinsi',
            'kab_kota' => 'Kota',
            'kecamatan' => 'Kecamatan',
            'desa' => 'Desa',
            'latitude' => 0,
            'longitude' => 0,
            'status_aktif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    public function registerAnggota(Request $request)
    {
        $payload = $request->validate([
            'nama' => ['nullable', 'string', 'max:150'],
            'email' => ['required', 'email'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6'],
        ]);
        $koperasiId = $this->resolveKoperasiId($request);
        $exists = Anggota::query()
            ->where('koperasi_id', $koperasiId)
            ->where('email', strtolower(trim($payload['email'])))
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages(['email' => 'Email sudah terdaftar']);
        }
        $row = new Anggota;
        $row->koperasi_id = $koperasiId;
        $row->nomor_anggota = 'A'.substr(md5($payload['email'].microtime(true)), 0, 8);
        $row->nama_anggota = $payload['nama'] ?? '';
        $row->email = strtolower(trim($payload['email']));
        $row->telepon = $payload['telepon'] ?? '';
        $row->password = Hash::make($payload['password']);
        $row->status = 'pending';
        $row->save();
        $token = $row->createToken('komera-mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'role' => 'anggota',
            'koperasi_id' => $row->koperasi_id ?? null,
            'user' => ['id' => $row->id, 'name' => $row->nama_anggota, 'email' => $row->email],
        ], 201);
    }

    public function registerMerchant(Request $request)
    {
        $payload = $request->validate([
            'nama_toko' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6'],
            'alamat' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);
        $koperasiId = $this->resolveKoperasiId($request);
        $exists = Merchant::query()
            ->where('koperasi_id', $koperasiId)
            ->where('email', strtolower(trim($payload['email'])))
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages(['email' => 'Email sudah terdaftar']);
        }
        $row = new Merchant;
        $row->koperasi_id = $koperasiId;
        $row->nama_toko = $payload['nama_toko'];
        $row->email = strtolower(trim($payload['email']));
        $row->telepon = $payload['telepon'] ?? '';
        $row->password = Hash::make($payload['password']);
        $row->alamat = $payload['alamat'] ?? '';
        $row->latitude = $payload['latitude'] ?? 0;
        $row->longitude = $payload['longitude'] ?? 0;
        $row->status = 'pending';
        $row->save();
        $token = $row->createToken('komera-mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'role' => 'merchant',
            'koperasi_id' => $row->koperasi_id ?? null,
            'user' => ['id' => $row->id, 'name' => $row->nama_toko, 'email' => $row->email],
        ], 201);
    }

    public function applySeller(Request $request)
    {
        $user = $request->user();
        if (! $user || ! ($user instanceof Anggota)) {
            return response()->json(['message' => 'Hanya anggota yang dapat mengajukan seller'], 403);
        }
        $payload = $request->validate([
            'nama_toko' => ['required', 'string', 'max:150'],
            'deskripsi' => ['required', 'string'],
            'alamat' => ['required', 'string'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'nib' => ['nullable', 'string', 'max:50'],
            'pirt' => ['nullable', 'string', 'max:50'],
        ]);
        $koperasiId = (int) ($request->attributes->get('koperasi_id') ?? $user->koperasi_id);

        $row = Merchant::query()
            ->where('koperasi_id', $koperasiId)
            ->where('anggota_id', $user->id)
            ->first();
        if (! $row) {
            $row = new Merchant;
            $row->koperasi_id = $koperasiId;
            $row->anggota_id = $user->id;
        }
        $row->nama_toko = $payload['nama_toko'];
        $row->deskripsi = $payload['deskripsi'];
        $row->alamat = $payload['alamat'];
        $row->latitude = (float) $payload['latitude'];
        $row->longitude = (float) $payload['longitude'];
        $row->nib = $payload['nib'] ?? null;
        $row->pirt = $payload['pirt'] ?? null;
        if (empty($row->status) || $row->status !== 'aktif') {
            $row->status = 'pending';
        }
        $row->save();

        return response()->json([
            'message' => 'Pengajuan seller diterima. Menunggu approval backoffice koperasi.',
            'id' => $row->id,
            'status' => $row->status,
        ], 201);
    }

    public function switchToMerchant(Request $request)
    {
        $user = $request->user();
        if (! $user || ! ($user instanceof Anggota)) {
            return response()->json(['message' => 'Hanya anggota yang bisa beralih ke seller'], 403);
        }
        $koperasiId = (int) ($request->attributes->get('koperasi_id') ?? $user->koperasi_id);
        $m = Merchant::query()
            ->where('koperasi_id', $koperasiId)
            ->where('anggota_id', $user->id)
            ->where('status', 'aktif')
            ->first();
        if (! $m) {
            $m = new Merchant();
            $m->koperasi_id = $koperasiId;
            $m->anggota_id = $user->id;
            $m->nama_toko = $user->nama_anggota ?: 'Toko Anggota';
            $m->nama_pemilik = $user->nama_anggota ?: null;
            $m->email = $user->email ?: null;
            $m->telepon = $user->telepon ?: null;
            $m->alamat = 'Alamat belum diisi';
            $m->kota = 'Kota';
            $m->provinsi = 'Provinsi';
            $m->latitude = 0;
            $m->longitude = 0;
            $m->aktif_delivery_toko = true;
            $m->biaya_delivery_toko = 0;
            $m->aktif_delivery_kojek = true;
            $m->radius_layanan_km = 5;
            $m->status = 'aktif';
            $m->save();
        }
        $token = $m->createToken('komera-mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'role' => 'merchant',
            'koperasi_id' => $m->koperasi_id ?? null,
            'user' => [
                'id' => $m->id,
                'name' => $m->nama_toko,
                'email' => $m->email,
            ],
        ]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'type' => ['nullable', 'in:anggota,merchant'],
        ]);

        $email = strtolower(trim($validated['email']));
        $password = $validated['password'];
        $koperasiId = $this->resolveKoperasiId($request);

        $tryLogin = function ($role) use ($email, $password, $koperasiId) {
            if ($role === 'anggota') {
                $user = Anggota::query()
                    ->where('email', $email)
                    ->where('koperasi_id', $koperasiId)
                    ->first();
                if ($user && $this->checkPassword($user->password, $password)) {
                    $token = $user->createToken('komera-mobile')->plainTextToken;

                    return [$user, $token, 'anggota'];
                }
            } elseif ($role === 'merchant') {
                $user = Merchant::query()
                    ->where('email', $email)
                    ->where('koperasi_id', $koperasiId)
                    ->first();
                if ($user && $this->checkPassword($user->password, $password)) {
                    $token = $user->createToken('komera-mobile')->plainTextToken;

                    return [$user, $token, 'merchant'];
                }
            }

            return null;
        };

        $type = $validated['type'] ?? null;
        $result = null;
        if ($type) {
            $result = $tryLogin($type);
        } else {
            $result = $tryLogin('anggota') ?: $tryLogin('merchant');
        }

        if (! $result) {
            throw ValidationException::withMessages(['email' => 'Email atau password salah.']);
        }

        [$user, $token, $role] = $result;

        return response()->json([
            'token' => $token,
            'role' => $role,
            'koperasi_id' => $user->koperasi_id ?? null,
            'user' => [
                'id' => $user->id,
                'name' => $role === 'anggota' ? $user->nama_anggota : $user->nama_toko,
                'email' => $user->email,
            ],
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $role = $user instanceof Anggota ? 'anggota' : ($user instanceof Merchant ? 'merchant' : 'unknown');

        return response()->json([
            'role' => $role,
            'user' => [
                'id' => $user->id,
                'name' => $role === 'anggota' ? ($user->nama_anggota ?? $user->name ?? '') : ($user->nama_toko ?? $user->name ?? ''),
                'email' => $user->email ?? null,
            ],
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $role = $user instanceof Anggota ? 'anggota' : ($user instanceof Merchant ? 'merchant' : 'unknown');
        if ($role === 'anggota') {
            $merchant = Merchant::query()
                ->where('koperasi_id', (int) $request->attributes->get('koperasi_id'))
                ->where('anggota_id', $user->id)
                ->first();
            return response()->json([
                'role' => 'anggota',
                'user' => [
                    'id' => $user->id,
                    'nama_anggota' => $user->nama_anggota,
                    'email' => $user->email,
                    'telepon' => $user->telepon,
                    'status' => $user->status,
                ],
                'seller' => $merchant ? [
                    'merchant_id' => $merchant->id,
                    'status' => $merchant->status,
                    'nama_toko' => $merchant->nama_toko,
                ] : null,
            ]);
        }
        if ($role === 'merchant') {
            return response()->json([
                'role' => 'merchant',
                'user' => [
                    'id' => $user->id,
                    'nama_toko' => $user->nama_toko,
                    'email' => $user->email,
                    'telepon' => $user->telepon,
                    'alamat' => $user->alamat,
                    'status' => $user->status,
                ],
            ]);
        }

        return response()->json(['message' => 'Role tidak dikenali'], 422);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        if ($user instanceof Anggota) {
            $payload = $request->validate([
                'nama_anggota' => ['nullable', 'string', 'max:150'],
                'email' => ['nullable', 'email'],
                'telepon' => ['nullable', 'string', 'max:20'],
                'password' => ['nullable', 'string', 'min:6'],
            ]);
            if (isset($payload['email'])) {
                $exists = Anggota::query()
                    ->where('koperasi_id', $user->koperasi_id)
                    ->where('email', strtolower(trim($payload['email'])))
                    ->where('id', '<>', $user->id)
                    ->exists();
                if ($exists) {
                    throw ValidationException::withMessages(['email' => 'Email sudah dipakai']);
                }
                $user->email = strtolower(trim($payload['email']));
            }
            if (isset($payload['nama_anggota'])) {
                $user->nama_anggota = $payload['nama_anggota'];
            }
            if (isset($payload['telepon'])) {
                $user->telepon = $payload['telepon'];
            }
            if (! empty($payload['password'])) {
                $user->password = Hash::make($payload['password']);
            }
            $user->save();

            return $this->profile($request);
        }
        if ($user instanceof Merchant) {
            $payload = $request->validate([
                'nama_toko' => ['nullable', 'string', 'max:150'],
                'email' => ['nullable', 'email'],
                'telepon' => ['nullable', 'string', 'max:20'],
                'alamat' => ['nullable', 'string'],
                'password' => ['nullable', 'string', 'min:6'],
            ]);
            if (isset($payload['email'])) {
                $exists = Merchant::query()
                    ->where('koperasi_id', $user->koperasi_id)
                    ->where('email', strtolower(trim($payload['email'])))
                    ->where('id', '<>', $user->id)
                    ->exists();
                if ($exists) {
                    throw ValidationException::withMessages(['email' => 'Email sudah dipakai']);
                }
                $user->email = strtolower(trim($payload['email']));
            }
            if (isset($payload['nama_toko'])) {
                $user->nama_toko = $payload['nama_toko'];
            }
            if (isset($payload['telepon'])) {
                $user->telepon = $payload['telepon'];
            }
            if (isset($payload['alamat'])) {
                $user->alamat = $payload['alamat'];
            }
            if (! empty($payload['password'])) {
                $user->password = Hash::make($payload['password']);
            }
            $user->save();

            return $this->profile($request);
        }

        return response()->json(['message' => 'Role tidak dikenali'], 422);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $token = $user ? $user->currentAccessToken() : null;
        if ($token) { $token->delete(); }

        return response()->json(['message' => 'Logged out']);
    }

    public function googleClient(Request $request)
    {
        $id = env('GOOGLE_WEB_CLIENT_ID', '');

        return response()->json(['web_client_id' => $id])
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With');
    }

    public function loginGoogle(Request $request)
    {
        $validated = $request->validate([
            'google_id' => ['nullable', 'string', 'required_without_all:id_token,access_token'],
            'email' => ['nullable', 'email'],
            'name' => ['nullable', 'string'],
            'id_token' => ['nullable', 'string'],
            'access_token' => ['nullable', 'string'],
        ]);
        $koperasiId = $this->resolveKoperasiId($request);
        $googleId = $validated['google_id'] ?? null;
        $email = isset($validated['email']) ? strtolower(trim($validated['email'])) : null;
        $name = $validated['name'] ?? 'User Google';

        $idToken = $validated['id_token'] ?? null;
        if ($idToken) {
            try {
                $resp = Http::timeout(8)->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $idToken]);
                if ($resp->ok()) {
                    $info = $resp->json();
                    $sub = $info['sub'] ?? null;
                    $emailFromToken = $info['email'] ?? null;
                    if ($sub && $googleId && $googleId !== $sub) {
                        return response()->json(['message' => 'ID token tidak valid'], 422);
                    }
                    if (! $googleId && $sub) {
                        $googleId = $sub;
                    }
                    if (! $email && $emailFromToken) {
                        $email = $emailFromToken;
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('loginGoogle tokeninfo error', ['msg' => $e->getMessage()]);
            }
        }

        // Fallback: if google_id still empty and access_token available, query userinfo
        if (! $googleId && ! empty($validated['access_token'])) {
            try {
                $resp = Http::timeout(8)
                    ->withToken($validated['access_token'])
                    ->get('https://openidconnect.googleapis.com/v1/userinfo');
                if ($resp->ok()) {
                    $info = $resp->json();
                    $sub = $info['sub'] ?? null;
                    $emailFromToken = $info['email'] ?? null;
                    if ($sub) {
                        $googleId = $sub;
                    }
                    if (! $email && $emailFromToken) {
                        $email = $emailFromToken;
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('loginGoogle userinfo error', ['msg' => $e->getMessage()]);
            }
        }

        if (! $googleId) {
            return response()->json(['message' => 'google_id tidak ditemukan dari token'], 422);
        }

        $user = Anggota::query()
            ->where('koperasi_id', $koperasiId)
            ->where(function ($q) use ($googleId, $email) {
                $q->where('login_google_id', $googleId);
                if ($email) {
                    $q->orWhere('email', $email);
                }
            })
            ->first();
        if (! $user && $email) {
            $user = Anggota::query()
                ->where('email', $email)
                ->first();
            if ($user) {
                $koperasiId = (int) $user->koperasi_id;
            }
        }
        if (! $user) {
            $user = new Anggota;
            $user->koperasi_id = $koperasiId;
            $user->nomor_anggota = 'G'.substr(md5($googleId.microtime(true)), 0, 8);
            $user->nama_anggota = $name;
            $user->email = $email;
            $user->telepon = '';
            $user->password = null;
            $user->login_google_id = $googleId;
            $user->status = 'pending';
            $user->save();
        } else {
            $user->login_google_id = $googleId;
            if ($email && empty($user->email)) {
                $user->email = $email;
            }
            if (empty($user->nama_anggota)) {
                $user->nama_anggota = $name;
            }
            $user->save();
        }

        $token = $user->createToken('komera-mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'role' => 'anggota',
            'koperasi_id' => $user->koperasi_id ?? null,
            'user' => [
                'id' => $user->id,
                'name' => $user->nama_anggota,
                'email' => $user->email,
            ],
        ]);
    }

    private function checkPassword(?string $hashed, string $plain): bool
    {
        if (empty($hashed)) {
            return false;
        }
        if (Hash::check($plain, $hashed)) {
            return true;
        }

        // Fallback: accept plaintext for legacy records
        return hash_equals($hashed, $plain);
    }
}
