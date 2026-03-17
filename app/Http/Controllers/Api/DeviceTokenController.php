<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Merchant;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceTokenController extends Controller
{
    public function register(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $payload = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:50'],
        ]);
        $anggotaId = null;
        if ($user instanceof Anggota) {
            $anggotaId = (int) $user->id;
        } elseif ($user instanceof Merchant) {
            $anggotaId = (int) ($user->anggota_id ?? 0);
        }
        if (! $anggotaId) {
            return response()->json(['message' => 'User tidak terikat ke anggota'], 403);
        }
        DB::table('anggota_device_tokens')->updateOrInsert([
            'anggota_id' => $anggotaId,
            'token' => $payload['token'],
        ], [
            'platform' => $payload['platform'] ?? null,
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        return response()->json(['status' => 'ok']);
    }

    public function registerDriver(Request $request)
    {
        $user = $request->user();
        if (! $user || ! ($user instanceof Driver)) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $payload = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:50'],
        ]);
        DB::table('driver_device_tokens')->updateOrInsert([
            'driver_id' => (int) $user->id,
            'token' => $payload['token'],
        ], [
            'platform' => $payload['platform'] ?? null,
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        return response()->json(['status' => 'ok']);
    }
}
