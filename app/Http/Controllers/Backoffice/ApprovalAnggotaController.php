<?php

namespace App\Http\Controllers\Backoffice;

use App\Notifications\AnggotaApprovedNotification;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApprovalAnggotaController extends BaseController
{
    public function index()
    {
        $q = DB::table('anggota')
            ->join('koperasi', 'anggota.koperasi_id', '=', 'koperasi.id')
            ->select('anggota.*', 'koperasi.nama_koperasi')
            ->where('anggota.status', 'pending')
            ->orderByDesc('anggota.id');
        $user = Auth::user();
        if ($user && ! $user->hasRole('superadmin')) {
            $q->where('anggota.koperasi_id', $user->koperasi_id);
        }
        $items = $q->get();

        return view('approval_anggota.index', compact('items'));
    }

    public function approve(Request $request, $id)
    {
        $user = Auth::user();
        $row = DB::table('anggota')->where('id', $id)->first();
        if (! $row) {
            return redirect()->back()->with('status', 'Data tidak ditemukan');
        }
        if ($user && ! $user->hasRole('superadmin') && (int) $row->koperasi_id !== (int) $user->koperasi_id) {
            return redirect()->back()->with('status', 'Tidak diizinkan');
        }
        DB::table('anggota')->where('id', $id)->update([
            'status' => 'aktif',
            'updated_at' => now(),
        ]);

        $anggota = new \App\Models\Anggota;
        $anggota->forceFill((array) $row);
        $anggota->id = $row->id;
        $anggota->notify(new AnggotaApprovedNotification);

        try {
            $tokens = DB::table('anggota_device_tokens')->where('anggota_id', $id)->pluck('token')->all();
            if (! empty($tokens)) {
                app(FcmService::class)->sendToTokens(
                    tokens: $tokens,
                    title: 'Keanggotaan Disetujui',
                    body: 'Pengajuan Anda sebagai anggota koperasi telah disetujui.',
                    data: ['type' => 'anggota_approved']
                );
            }
        } catch (\Throwable $e) {
            // ignore push errors
        }

        return redirect()->back()->with('status', 'Anggota disetujui');
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        $row = DB::table('anggota')->where('id', $id)->first();
        if (! $row) {
            return redirect()->back()->with('status', 'Data tidak ditemukan');
        }
        if ($user && ! $user->hasRole('superadmin') && (int) $row->koperasi_id !== (int) $user->koperasi_id) {
            return redirect()->back()->with('status', 'Tidak diizinkan');
        }
        DB::table('anggota')->where('id', $id)->update([
            'status' => 'ditolak',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('status', 'Pengajuan ditolak');
    }
}
