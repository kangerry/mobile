<?php

namespace App\Http\Middleware;

use App\Models\Anggota;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnsureAnggotaActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && ($user instanceof Anggota) && ($user->status ?? null) === 'aktif') {
            $request->attributes->set('anggota_id', (int) $user->id);
            $request->attributes->set('anggota_profile', [
                'id' => (int) $user->id,
                'nama' => (string) ($user->nama_anggota ?? ''),
                'email' => (string) ($user->email ?? ''),
                'telepon' => (string) ($user->telepon ?? ''),
            ]);
            return $next($request);
        }

        $email = $user->email ?? null;
        $koperasiId = (int) ($request->attributes->get('koperasi_id') ?? ($user->koperasi_id ?? 0));
        if ($email && $koperasiId) {
            $row = DB::table('anggota')
                ->select('id', 'nama_anggota', 'email', 'telepon', 'status')
                ->where('koperasi_id', $koperasiId)
                ->where('email', strtolower(trim($email)))
                ->where('status', 'aktif')
                ->first();
            if ($row) {
                $request->attributes->set('anggota_id', (int) $row->id);
                $request->attributes->set('anggota_profile', [
                    'id' => (int) $row->id,
                    'nama' => (string) ($row->nama_anggota ?? ''),
                    'email' => (string) ($row->email ?? ''),
                    'telepon' => (string) ($row->telepon ?? ''),
                ]);
                return $next($request);
            }
        }

        return response()->json(['message' => 'Hanya anggota aktif yang dapat mengakses fitur ini'], 403);
    }
}
