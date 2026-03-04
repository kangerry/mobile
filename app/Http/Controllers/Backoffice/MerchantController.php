<?php

namespace App\Http\Controllers\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MerchantController extends BaseController
{
    public function index()
    {
        $query = DB::table('merchant')
            ->join('koperasi', 'merchant.koperasi_id', '=', 'koperasi.id')
            ->select('merchant.*', 'koperasi.nama_koperasi')
            ->orderByDesc('merchant.id');
        $user = Auth::user();
        if ($user && ! $user->hasRole('superadmin')) {
            $query->where('merchant.koperasi_id', $user->koperasi_id);
        }
        $items = $query->get();

        return view('merchant.index', compact('items'));
    }

    public function create()
    {
        $user = Auth::user();
        $koperasisQuery = DB::table('koperasi')->select('id', 'nama_koperasi')->orderBy('nama_koperasi');
        if ($user && ! $user->hasRole('superadmin')) {
            $koperasisQuery->where('id', $user->koperasi_id);
        }
        $koperasis = $koperasisQuery->get();

        return view('merchant.create', compact('koperasis'));
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'koperasi_id' => 'required|exists:koperasi,id',
            'nama_toko' => 'required|string|max:150',
            'deskripsi' => 'nullable|string',
            'nama_pemilik' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:150',
            'telepon' => 'nullable|string|max:20',
            'alamat' => 'required|string',
            'kota' => 'nullable|string|max:100',
            'provinsi' => 'nullable|string|max:100',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'nib' => 'nullable|string|max:50',
            'pirt' => 'nullable|string|max:50',
            'anggota_id' => 'nullable|exists:anggota,id',
            'status' => 'nullable|string|max:20',
        ]);
        $user = Auth::user();
        $koperasiId = ($user && $user->hasRole('superadmin')) ? (int) $v['koperasi_id'] : (int) ($user->koperasi_id ?? $v['koperasi_id']);
        DB::table('merchant')->insert([
            'koperasi_id' => $koperasiId,
            'nama_toko' => $v['nama_toko'],
            'deskripsi' => $v['deskripsi'] ?? null,
            'nama_pemilik' => $v['nama_pemilik'] ?? null,
            'email' => $v['email'] ?? null,
            'telepon' => $v['telepon'] ?? null,
            'alamat' => $v['alamat'],
            'kota' => $v['kota'] ?? null,
            'provinsi' => $v['provinsi'] ?? null,
            'latitude' => $v['latitude'],
            'longitude' => $v['longitude'],
            'nib' => $v['nib'] ?? null,
            'pirt' => $v['pirt'] ?? null,
            'anggota_id' => $v['anggota_id'] ?? null,
            'status' => $v['status'] ?? 'aktif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('merchant.index')->with('status', 'Data tersimpan');
    }

    public function edit($id)
    {
        $user = Auth::user();
        $row = DB::table('merchant')->where('id', $id)->first();
        $koperasisQuery = DB::table('koperasi')->select('id', 'nama_koperasi')->orderBy('nama_koperasi');
        if ($user && ! $user->hasRole('superadmin')) {
            $koperasisQuery->where('id', $user->koperasi_id);
        }
        $koperasis = $koperasisQuery->get();

        return view('merchant.edit', compact('row', 'koperasis'));
    }

    public function update(Request $request, $id)
    {
        $v = $request->validate([
            'koperasi_id' => 'required|exists:koperasi,id',
            'nama_toko' => 'required|string|max:150',
            'deskripsi' => 'nullable|string',
            'nama_pemilik' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:150',
            'telepon' => 'nullable|string|max:20',
            'alamat' => 'required|string',
            'kota' => 'nullable|string|max:100',
            'provinsi' => 'nullable|string|max:100',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'nib' => 'nullable|string|max:50',
            'pirt' => 'nullable|string|max:50',
            'anggota_id' => 'nullable|exists:anggota,id',
            'status' => 'nullable|string|max:20',
        ]);
        $user = Auth::user();
        $koperasiId = ($user && $user->hasRole('superadmin')) ? (int) $v['koperasi_id'] : (int) ($user->koperasi_id ?? $v['koperasi_id']);
        DB::table('merchant')->where('id', $id)->update([
            'koperasi_id' => $koperasiId,
            'nama_toko' => $v['nama_toko'],
            'deskripsi' => $v['deskripsi'] ?? null,
            'nama_pemilik' => $v['nama_pemilik'] ?? null,
            'email' => $v['email'] ?? null,
            'telepon' => $v['telepon'] ?? null,
            'alamat' => $v['alamat'],
            'kota' => $v['kota'] ?? null,
            'provinsi' => $v['provinsi'] ?? null,
            'latitude' => $v['latitude'],
            'longitude' => $v['longitude'],
            'nib' => $v['nib'] ?? null,
            'pirt' => $v['pirt'] ?? null,
            'anggota_id' => $v['anggota_id'] ?? null,
            'status' => $v['status'] ?? 'aktif',
            'updated_at' => now(),
        ]);

        return redirect()->route('merchant.index')->with('status', 'Data diperbarui');
    }

    public function destroy($id)
    {
        DB::table('merchant')->where('id', $id)->delete();

        return redirect()->route('merchant.index')->with('status', 'Data dihapus');
    }

    public function show($id)
    {
        return redirect()->route('merchant.edit', $id);
    }
}
