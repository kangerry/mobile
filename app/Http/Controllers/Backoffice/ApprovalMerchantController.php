<?php

namespace App\Http\Controllers\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApprovalMerchantController extends BaseController
{
    public function index()
    {
        $q = DB::table('merchant')
            ->join('koperasi', 'merchant.koperasi_id', '=', 'koperasi.id')
            ->leftJoin('anggota', 'merchant.anggota_id', '=', 'anggota.id')
            ->select('merchant.*', 'koperasi.nama_koperasi', 'anggota.nama_anggota')
            ->where('merchant.status', 'pending')
            ->orderByDesc('merchant.id');
        $user = Auth::user();
        if ($user && !$user->hasRole('superadmin')) {
            $q->where('merchant.koperasi_id', $user->koperasi_id);
        }
        $items = $q->get();

        return view('approval_merchant.index', compact('items'));
    }

    public function approve(Request $request, $id)
    {
        $user = Auth::user();
        $row = DB::table('merchant')->where('id', $id)->first();
        if (!$row) {
            return redirect()->back()->with('status', 'Data tidak ditemukan');
        }
        if ($user && !$user->hasRole('superadmin') && (int) $row->koperasi_id !== (int) $user->koperasi_id) {
            return redirect()->back()->with('status', 'Tidak diizinkan');
        }
        DB::table('merchant')->where('id', $id)->update([
            'status' => 'aktif',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('status', 'Seller disetujui');
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        $row = DB::table('merchant')->where('id', $id)->first();
        if (!$row) {
            return redirect()->back()->with('status', 'Data tidak ditemukan');
        }
        if ($user && !$user->hasRole('superadmin') && (int) $row->koperasi_id !== (int) $user->koperasi_id) {
            return redirect()->back()->with('status', 'Tidak diizinkan');
        }
        DB::table('merchant')->where('id', $id)->update([
            'status' => 'ditolak',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('status', 'Pengajuan ditolak');
    }
}
