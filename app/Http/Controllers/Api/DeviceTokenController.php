<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceTokenController extends Controller
{
    public function register(Request $request)
    {
        $user = $request->user();
        if (! $user || ! ($user instanceof Anggota)) {
            return response()->json(['message' => 'Hanya anggota yang dapat mendaftarkan device token'], 403);
        }
        $payload = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:50'],
        ]);
        DB::table('anggota_device_tokens')->updateOrInsert(
            ['anggota_id' => $user->id, 'token' => $payload['token']],
            ['platform' => $payload['platform'] ?? null, 'updated_at' => now(), 'created_at' => now()]
        );

        return response()->json(['status' => 'ok']);
    }
}
