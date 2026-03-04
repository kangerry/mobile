<?php

namespace App\Http\Controllers\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DriverController extends BaseController
{
    public function index()
    {
        $query = DB::table('driver')
            ->join('koperasi', 'driver.koperasi_id', '=', 'koperasi.id')
            ->select('driver.*', 'koperasi.nama_koperasi')
            ->orderByDesc('driver.id');
        $user = Auth::user();
        if ($user && ! $user->hasRole('superadmin')) {
            $query->where('driver.koperasi_id', $user->koperasi_id);
        }
        $items = $query->get();

        return view('driver.index', compact('items'));
    }

    public function create()
    {
        $user = Auth::user();
        $kopQuery = DB::table('koperasi')->select('id', 'nama_koperasi')->orderBy('nama_koperasi');
        if ($user && ! $user->hasRole('superadmin')) {
            $kopQuery->where('id', $user->koperasi_id);
        }
        $koperasis = $kopQuery->get();

        return view('driver.create', compact('koperasis'));
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'koperasi_id' => 'required|exists:koperasi,id',
            'nama_driver' => 'required|string|max:150',
            'email' => 'nullable|email|max:150',
            'telepon' => 'nullable|string|max:20',
            'jenis_kendaraan' => 'nullable|string|max:20',
            'plat_nomor' => 'nullable|string|max:20',
            'nomor_sim' => 'nullable|string|max:50',
        ]);
        $user = Auth::user();
        $koperasiId = ($user && $user->hasRole('superadmin')) ? (int) $v['koperasi_id'] : (int) ($user->koperasi_id ?? $v['koperasi_id']);
        DB::table('driver')->insert([
            'koperasi_id' => $koperasiId,
            'nama_driver' => $v['nama_driver'],
            'email' => $v['email'] ?? null,
            'telepon' => $v['telepon'] ?? null,
            'jenis_kendaraan' => $v['jenis_kendaraan'] ?? null,
            'plat_nomor' => $v['plat_nomor'] ?? null,
            'nomor_sim' => $v['nomor_sim'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('driver.index')->with('status', 'Data tersimpan');
    }

    public function edit($id)
    {
        $user = Auth::user();
        $row = DB::table('driver')->where('id', $id)->first();
        $kopQuery = DB::table('koperasi')->select('id', 'nama_koperasi')->orderBy('nama_koperasi');
        if ($user && ! $user->hasRole('superadmin')) {
            $kopQuery->where('id', $user->koperasi_id);
        }
        $koperasis = $kopQuery->get();

        return view('driver.edit', compact('row', 'koperasis'));
    }

    public function update(Request $request, $id)
    {
        $v = $request->validate([
            'koperasi_id' => 'required|exists:koperasi,id',
            'nama_driver' => 'required|string|max:150',
            'email' => 'nullable|email|max:150',
            'telepon' => 'nullable|string|max:20',
            'jenis_kendaraan' => 'nullable|string|max:20',
            'plat_nomor' => 'nullable|string|max:20',
            'nomor_sim' => 'nullable|string|max:50',
        ]);
        $user = Auth::user();
        $koperasiId = ($user && $user->hasRole('superadmin')) ? (int) $v['koperasi_id'] : (int) ($user->koperasi_id ?? $v['koperasi_id']);
        DB::table('driver')->where('id', $id)->update([
            'koperasi_id' => $koperasiId,
            'nama_driver' => $v['nama_driver'],
            'email' => $v['email'] ?? null,
            'telepon' => $v['telepon'] ?? null,
            'jenis_kendaraan' => $v['jenis_kendaraan'] ?? null,
            'plat_nomor' => $v['plat_nomor'] ?? null,
            'nomor_sim' => $v['nomor_sim'] ?? null,
            'updated_at' => now(),
        ]);

        return redirect()->route('driver.index')->with('status', 'Data diperbarui');
    }

    public function destroy($id)
    {
        DB::table('driver')->where('id', $id)->delete();

        return redirect()->route('driver.index')->with('status', 'Data dihapus');
    }

    public function show($id)
    {
        return redirect()->route('driver.edit', $id);
    }
}
