<?php

namespace App\Http\Controllers\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnggotaController extends BaseController
{
    public function index()
    {
        $query = DB::table('anggota')
            ->join('koperasi', 'anggota.koperasi_id', '=', 'koperasi.id')
            ->select('anggota.*', 'koperasi.nama_koperasi')
            ->orderByDesc('anggota.id');
        $user = Auth::user();
        if ($user && ! $user->hasRole('superadmin')) {
            $query->where('anggota.koperasi_id', $user->koperasi_id);
        }
        $items = $query->get();

        return view('anggota.index', compact('items'));
    }

    public function create()
    {
        $user = Auth::user();
        $kopQuery = DB::table('koperasi')->select('id', 'nama_koperasi')->orderBy('nama_koperasi');
        if ($user && ! $user->hasRole('superadmin')) {
            $kopQuery->where('id', $user->koperasi_id);
        }
        $koperasis = $kopQuery->get();

        return view('anggota.create', compact('koperasis'));
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'koperasi_id' => 'required|exists:koperasi,id',
            'nomor_anggota' => 'required|string|max:50|unique:anggota,nomor_anggota',
            'nama_anggota' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'telepon' => 'required|string|max:20',
            'status' => 'nullable|string|max:20',
        ]);
        $user = Auth::user();
        $koperasiId = ($user && $user->hasRole('superadmin')) ? (int) $v['koperasi_id'] : (int) ($user->koperasi_id ?? $v['koperasi_id']);
        DB::table('anggota')->insert([
            'koperasi_id' => $koperasiId,
            'nomor_anggota' => $v['nomor_anggota'],
            'nama_anggota' => $v['nama_anggota'],
            'email' => strtolower(trim($v['email'])),
            'telepon' => $v['telepon'],
            'status' => $v['status'] ?? 'aktif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('anggota.index')->with('status', 'Data tersimpan');
    }

    public function edit($id)
    {
        $user = Auth::user();
        $row = DB::table('anggota')->where('id', $id)->first();
        $kopQuery = DB::table('koperasi')->select('id', 'nama_koperasi')->orderBy('nama_koperasi');
        if ($user && ! $user->hasRole('superadmin')) {
            $kopQuery->where('id', $user->koperasi_id);
        }
        $koperasis = $kopQuery->get();

        return view('anggota.edit', compact('row', 'koperasis'));
    }

    public function update(Request $request, $id)
    {
        $v = $request->validate([
            'koperasi_id' => 'required|exists:koperasi,id',
            'nomor_anggota' => 'required|string|max:50|unique:anggota,nomor_anggota,'.$id,
            'nama_anggota' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'telepon' => 'required|string|max:20',
            'status' => 'nullable|string|max:20',
        ]);
        $user = Auth::user();
        $koperasiId = ($user && $user->hasRole('superadmin')) ? (int) $v['koperasi_id'] : (int) ($user->koperasi_id ?? $v['koperasi_id']);
        DB::table('anggota')->where('id', $id)->update([
            'koperasi_id' => $koperasiId,
            'nomor_anggota' => $v['nomor_anggota'],
            'nama_anggota' => $v['nama_anggota'],
            'email' => strtolower(trim($v['email'])),
            'telepon' => $v['telepon'],
            'status' => $v['status'] ?? 'aktif',
            'updated_at' => now(),
        ]);

        return redirect()->route('anggota.index')->with('status', 'Data diperbarui');
    }

    public function destroy($id)
    {
        DB::table('anggota')->where('id', $id)->delete();

        return redirect()->route('anggota.index')->with('status', 'Data dihapus');
    }

    public function show($id)
    {
        return redirect()->route('anggota.edit', $id);
    }
}
