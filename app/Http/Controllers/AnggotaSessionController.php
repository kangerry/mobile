<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AnggotaSessionController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $user = Anggota::query()->where('email', strtolower(trim($credentials['email'])))->first();
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Kredensial salah'], 422);
        }
        Auth::guard('anggota')->login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login berhasil',
            'user' => [
                'id' => $user->id,
                'nama_anggota' => $user->nama_anggota,
                'email' => $user->email,
                'telepon' => $user->telepon,
                'koperasi_id' => $user->koperasi_id,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('anggota')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logout berhasil']);
    }

    public function me(Request $request)
    {
        $user = Auth::guard('anggota')->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'nama_anggota' => $user->nama_anggota,
                'email' => $user->email,
                'telepon' => $user->telepon,
                'koperasi_id' => $user->koperasi_id,
                'status' => $user->status,
            ],
        ]);
    }
}
