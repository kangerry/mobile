<?php

namespace App\Http\Controllers\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PesananOjekController extends BaseController
{
    public function index()
    {
        $query = DB::table('pesanan_ojek')
            ->join('anggota', 'pesanan_ojek.anggota_id', '=', 'anggota.id')
            ->leftJoin('driver', 'pesanan_ojek.driver_id', '=', 'driver.id')
            ->select('pesanan_ojek.*', 'anggota.nama_anggota', 'driver.nama_driver')
            ->orderByDesc('pesanan_ojek.id');
        $user = Auth::user();
        if ($user && ! $user->hasRole('superadmin')) {
            $query->where('pesanan_ojek.koperasi_id', $user->koperasi_id);
        }
        $items = $query->get();

        return view('pesanan_ojek.index', compact('items'));
    }

    public function create()
    {
        $user = Auth::user();
        $kopQuery = DB::table('koperasi')->select('id', 'nama_koperasi')->orderBy('nama_koperasi');
        $anggotaQuery = DB::table('anggota')->select('id', 'nama_anggota')->orderBy('nama_anggota');
        $driverQuery = DB::table('driver')->select('id', 'nama_driver')->orderBy('nama_driver');
        if ($user && ! $user->hasRole('superadmin')) {
            $kopQuery->where('id', $user->koperasi_id);
            $anggotaQuery->where('koperasi_id', $user->koperasi_id);
            $driverQuery->where('koperasi_id', $user->koperasi_id);
        }
        $koperasis = $kopQuery->get();
        $anggota = $anggotaQuery->get();
        $drivers = $driverQuery->get();

        return view('pesanan_ojek.create', compact('koperasis', 'anggota', 'drivers'));
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'koperasi_id' => 'required|exists:koperasi,id',
            'nomor_pesanan' => 'required|string|max:50|unique:pesanan_ojek,nomor_pesanan',
            'anggota_id' => 'required|exists:anggota,id',
            'driver_id' => 'nullable|exists:driver,id',
            'total_bayar' => 'nullable|numeric',
            'status' => 'nullable|string|max:30',
        ]);
        $user = Auth::user();
        $koperasiId = ($user && $user->hasRole('superadmin')) ? (int) $v['koperasi_id'] : (int) ($user->koperasi_id ?? $v['koperasi_id']);
        DB::table('pesanan_ojek')->insert([
            'koperasi_id' => $koperasiId,
            'nomor_pesanan' => $v['nomor_pesanan'],
            'anggota_id' => (int) $v['anggota_id'],
            'driver_id' => isset($v['driver_id']) ? (int) $v['driver_id'] : null,
            'total_bayar' => $v['total_bayar'] ?? null,
            'status' => $v['status'] ?? 'baru',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('pesanan-ojek.index')->with('status', 'Data tersimpan');
    }

    public function edit($id)
    {
        $user = Auth::user();
        $row = DB::table('pesanan_ojek')->where('id', $id)->first();
        $kopQuery = DB::table('koperasi')->select('id', 'nama_koperasi')->orderBy('nama_koperasi');
        $anggotaQuery = DB::table('anggota')->select('id', 'nama_anggota')->orderBy('nama_anggota');
        $driverQuery = DB::table('driver')->select('id', 'nama_driver')->orderBy('nama_driver');
        if ($user && ! $user->hasRole('superadmin')) {
            $kopQuery->where('id', $user->koperasi_id);
            $anggotaQuery->where('koperasi_id', $user->koperasi_id);
            $driverQuery->where('koperasi_id', $user->koperasi_id);
        }
        $koperasis = $kopQuery->get();
        $anggota = $anggotaQuery->get();
        $drivers = $driverQuery->get();

        return view('pesanan_ojek.edit', compact('row', 'koperasis', 'anggota', 'drivers'));
    }

    public function update(Request $request, $id)
    {
        $v = $request->validate([
            'koperasi_id' => 'required|exists:koperasi,id',
            'nomor_pesanan' => 'required|string|max:50|unique:pesanan_ojek,nomor_pesanan,'.$id,
            'anggota_id' => 'required|exists:anggota,id',
            'driver_id' => 'nullable|exists:driver,id',
            'total_bayar' => 'nullable|numeric',
            'status' => 'nullable|string|max:30',
        ]);
        DB::table('pesanan_ojek')->where('id', $id)->update([
            'koperasi_id' => (int) $v['koperasi_id'],
            'nomor_pesanan' => $v['nomor_pesanan'],
            'anggota_id' => (int) $v['anggota_id'],
            'driver_id' => isset($v['driver_id']) ? (int) $v['driver_id'] : null,
            'total_bayar' => $v['total_bayar'] ?? null,
            'status' => $v['status'] ?? 'baru',
            'updated_at' => now(),
        ]);

        return redirect()->route('pesanan-ojek.index')->with('status', 'Data diperbarui');
    }

    public function destroy($id)
    {
        DB::table('pesanan_ojek')->where('id', $id)->delete();

        return redirect()->route('pesanan-ojek.index')->with('status', 'Data dihapus');
    }

    public function show($id)
    {
        return redirect()->route('pesanan-ojek.edit', $id);
    }
}
