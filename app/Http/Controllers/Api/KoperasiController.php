<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

class KoperasiController extends BaseController
{
    public function index()
    {
        $rows = DB::table('koperasi')
            ->select('id', 'kode_koperasi', 'nama_koperasi')
            ->orderBy('nama_koperasi')
            ->get()
            ->map(function ($r) {
                return [
                    'id' => (int) $r->id,
                    'kode' => $r->kode_koperasi,
                    'nama' => $r->nama_koperasi,
                ];
            });

        return response()->json(['data' => $rows]);
    }
}
