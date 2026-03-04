<?php

namespace App\Http\Controllers\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApprovalDriverController extends BaseController
{
    public function index()
    {
        $q = DB::table('driver')
            ->join('koperasi', 'driver.koperasi_id', '=', 'koperasi.id')
            ->select('driver.*', 'koperasi.nama_koperasi')
            ->where('driver.terverifikasi', false)
            ->orderByDesc('driver.id');
        $user = Auth::user();
        if ($user && !$user->hasRole('superadmin')) {
            $q->where('driver.koperasi_id', $user->koperasi_id);
        }
        $items = $q->get();

        return view('approval_driver.index', compact('items'));
    }

    public function approve(Request $request, $id)
    {
        $user = Auth::user();
        $row = DB::table('driver')->where('id', $id)->first();
        if (!$row) {
            return redirect()->back()->with('status', 'Data tidak ditemukan');
        }
        if ($user && !$user->hasRole('superadmin') && (int) $row->koperasi_id !== (int) $user->koperasi_id) {
            return redirect()->back()->with('status', 'Tidak diizinkan');
        }
        DB::table('driver')->where('id', $id)->update([
            'terverifikasi' => true,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('status', 'Driver disetujui');
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        $row = DB::table('driver')->where('id', $id)->first();
        if (!$row) {
            return redirect()->back()->with('status', 'Data tidak ditemukan');
        }
        if ($user && !$user->hasRole('superadmin') && (int) $row->koperasi_id !== (int) $user->koperasi_id) {
            return redirect()->back()->with('status', 'Tidak diizinkan');
        }
        DB::table('driver')->where('id', $id)->delete();

        return redirect()->back()->with('status', 'Pengajuan ditolak dan data dihapus');
    }
}
