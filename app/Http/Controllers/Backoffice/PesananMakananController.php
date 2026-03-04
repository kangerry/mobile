<?php

namespace App\Http\Controllers\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PesananMakananController extends BaseController
{
    public function index()
    {
        $query = DB::table('pesanan_makanan')
            ->join('anggota', 'pesanan_makanan.anggota_id', '=', 'anggota.id')
            ->join('merchant', 'pesanan_makanan.merchant_id', '=', 'merchant.id')
            ->select('pesanan_makanan.*', 'anggota.nama_anggota', 'merchant.nama_toko')
            ->orderByDesc('pesanan_makanan.id');
        $user = Auth::user();
        if ($user && ! $user->hasRole('superadmin')) {
            $query->where('pesanan_makanan.koperasi_id', $user->koperasi_id);
        }
        $items = $query->get();

        return view('pesanan_makanan.index', compact('items'));
    }

    public function create()
    {
        $user = Auth::user();
        $kopQuery = DB::table('koperasi')->select('id', 'nama_koperasi')->orderBy('nama_koperasi');
        $anggotaQuery = DB::table('anggota')->select('id', 'nama_anggota')->orderBy('nama_anggota');
        $merchantQuery = DB::table('merchant')->select('id', 'nama_toko')->orderBy('nama_toko');
        $driverQuery = DB::table('driver')->select('id', 'nama_driver')->orderBy('nama_driver');
        if ($user && ! $user->hasRole('superadmin')) {
            $kopQuery->where('id', $user->koperasi_id);
            $anggotaQuery->where('koperasi_id', $user->koperasi_id);
            $merchantQuery->where('koperasi_id', $user->koperasi_id);
            $driverQuery->where('koperasi_id', $user->koperasi_id);
        }
        $koperasis = $kopQuery->get();
        $anggota = $anggotaQuery->get();
        $merchants = $merchantQuery->get();
        $drivers = $driverQuery->get();

        return view('pesanan_makanan.create', compact('koperasis', 'anggota', 'merchants', 'drivers'));
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'koperasi_id' => 'required|exists:koperasi,id',
            'nomor_pesanan' => 'required|string|max:50|unique:pesanan_makanan,nomor_pesanan',
            'anggota_id' => 'required|exists:anggota,id',
            'merchant_id' => 'required|exists:merchant,id',
            'total_bayar' => 'nullable|numeric',
            'driver_id' => 'nullable|exists:driver,id',
            'tipe_pengiriman' => 'nullable|string|max:20',
            'status' => 'nullable|string|max:30',
        ]);
        $user = Auth::user();
        $koperasiId = ($user && $user->hasRole('superadmin')) ? (int) $v['koperasi_id'] : (int) ($user->koperasi_id ?? $v['koperasi_id']);
        DB::table('pesanan_makanan')->insert([
            'koperasi_id' => $koperasiId,
            'nomor_pesanan' => $v['nomor_pesanan'],
            'anggota_id' => (int) $v['anggota_id'],
            'merchant_id' => (int) $v['merchant_id'],
            'tipe_pengiriman' => $v['tipe_pengiriman'] ?? null,
            'driver_id' => isset($v['driver_id']) ? (int) $v['driver_id'] : null,
            'total_bayar' => $v['total_bayar'] ?? null,
            'status' => $v['status'] ?? 'baru',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('pesanan-makanan.index')->with('status', 'Data tersimpan');
    }

    public function edit($id)
    {
        $user = Auth::user();
        $row = DB::table('pesanan_makanan')->where('id', $id)->first();
        $kopQuery = DB::table('koperasi')->select('id', 'nama_koperasi')->orderBy('nama_koperasi');
        $anggotaQuery = DB::table('anggota')->select('id', 'nama_anggota')->orderBy('nama_anggota');
        $merchantQuery = DB::table('merchant')->select('id', 'nama_toko')->orderBy('nama_toko');
        $driverQuery = DB::table('driver')->select('id', 'nama_driver')->orderBy('nama_driver');
        if ($user && ! $user->hasRole('superadmin')) {
            $kopQuery->where('id', $user->koperasi_id);
            $anggotaQuery->where('koperasi_id', $user->koperasi_id);
            $merchantQuery->where('koperasi_id', $user->koperasi_id);
            $driverQuery->where('koperasi_id', $user->koperasi_id);
        }
        $koperasis = $kopQuery->get();
        $anggota = $anggotaQuery->get();
        $merchants = $merchantQuery->get();
        $drivers = $driverQuery->get();

        return view('pesanan_makanan.edit', compact('row', 'koperasis', 'anggota', 'merchants', 'drivers'));
    }

    public function update(Request $request, $id)
    {
        $v = $request->validate([
            'koperasi_id' => 'required|exists:koperasi,id',
            'nomor_pesanan' => 'required|string|max:50|unique:pesanan_makanan,nomor_pesanan,'.$id,
            'anggota_id' => 'required|exists:anggota,id',
            'merchant_id' => 'required|exists:merchant,id',
            'total_bayar' => 'nullable|numeric',
            'driver_id' => 'nullable|exists:driver,id',
            'tipe_pengiriman' => 'nullable|string|max:20',
            'status' => 'nullable|string|max:30',
        ]);
        DB::table('pesanan_makanan')->where('id', $id)->update([
            'koperasi_id' => (int) $v['koperasi_id'],
            'nomor_pesanan' => $v['nomor_pesanan'],
            'anggota_id' => (int) $v['anggota_id'],
            'merchant_id' => (int) $v['merchant_id'],
            'tipe_pengiriman' => $v['tipe_pengiriman'] ?? null,
            'driver_id' => isset($v['driver_id']) ? (int) $v['driver_id'] : null,
            'total_bayar' => $v['total_bayar'] ?? null,
            'status' => $v['status'] ?? 'baru',
            'updated_at' => now(),
        ]);

        return redirect()->route('pesanan-makanan.index')->with('status', 'Data diperbarui');
    }

    public function destroy($id)
    {
        DB::table('pesanan_makanan')->where('id', $id)->delete();

        return redirect()->route('pesanan-makanan.index')->with('status', 'Data dihapus');
    }

    public function show($id)
    {
        return redirect()->route('pesanan-makanan.edit', $id);
    }
}
