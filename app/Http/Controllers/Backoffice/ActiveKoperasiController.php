<?php

namespace App\Http\Controllers\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ActiveKoperasiController extends BaseController
{
    public function set(Request $request)
    {
        $user = Auth::user();
        if (! $user || ! $user->hasRole('superadmin')) {
            return redirect()->back()->with('status', 'Hanya superadmin yang dapat mengganti koperasi aktif');
        }
        $request->validate([
            'koperasi_id' => ['required', 'integer'],
        ]);
        $id = (int) $request->input('koperasi_id');
        $row = DB::table('koperasi')->select('id', 'nama_koperasi')->where('id', $id)->first();
        if (! $row) {
            return redirect()->back()->with('status', 'Koperasi tidak ditemukan');
        }
        $request->session()->put('active_koperasi_id', $row->id);
        $request->session()->put('active_koperasi_nama', $row->nama_koperasi);

        return redirect()->back()->with('status', 'Koperasi aktif diset: '.$row->nama_koperasi);
    }
}

