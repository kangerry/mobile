<?php

namespace App\Http\Controllers\Backoffice;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends BaseController
{
    public function index()
    {
        $today = Carbon::today();
        $has = fn ($t) => DB::getSchemaBuilder()->hasTable($t);
        $count = fn ($t) => $has($t) ? DB::table($t)->count() : 0;

        $stats = [
            'koperasi' => $count('koperasi'),
            'anggota' => $count('anggota'),
            'merchant' => $count('merchant'),
            'driver' => $count('driver'),
            'pesanan_hari_ini' => $has('pesanan_makanan') ? DB::table('pesanan_makanan')->whereDate('created_at', $today)->count() : 0,
            'transaksi_hari_ini' => $has('transaksi_gateway') ? DB::table('transaksi_gateway')->whereDate('created_at', $today)->count() : 0,
        ];

        return view('dashboard.index', compact('stats'));
    }
}
