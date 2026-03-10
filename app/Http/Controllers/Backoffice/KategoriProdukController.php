<?php

namespace App\Http\Controllers\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class KategoriProdukController extends BaseController
{
    public function index()
    {
        $query = DB::table('kategori_produk')
            ->join('koperasi', 'kategori_produk.koperasi_id', '=', 'koperasi.id')
            ->select('kategori_produk.*', 'koperasi.nama_koperasi')
            ->orderBy('nama_kategori');
        $user = Auth::user();
        if ($user && ! $user->hasRole('superadmin')) {
            $query->where('kategori_produk.koperasi_id', $user->koperasi_id);
        }
        $items = $query->get();

        return view('kategori_produk.index', compact('items'));
    }

    public function create()
    {
        $user = Auth::user();
        $kopQuery = DB::table('koperasi')->select('id', 'nama_koperasi')->orderBy('nama_koperasi');
        if ($user && ! $user->hasRole('superadmin')) {
            $kopQuery->where('id', $user->koperasi_id);
        }
        $koperasis = $kopQuery->get();

        return view('kategori_produk.create', compact('koperasis'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'koperasi_id' => 'required|exists:koperasi,id',
            'nama_kategori' => 'required|string|max:100',
            'gambar' => 'nullable|image|mimes:jpeg,png,webp,avif|max:2048',
        ]);
        $user = Auth::user();
        $koperasiId = ($user && $user->hasRole('superadmin')) ? (int) $validated['koperasi_id'] : (int) ($user->koperasi_id ?? $validated['koperasi_id']);
        $path = null;
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            if ($file) {
                $path = $file->store('kategori', 'public');
            }
        }
        DB::table('kategori_produk')->insert([
            'koperasi_id' => $koperasiId,
            'nama_kategori' => $validated['nama_kategori'],
            'gambar' => $path,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('kategori-produk.index')->with('status', 'Kategori ditambahkan');
    }

    public function edit($id)
    {
        $row = DB::table('kategori_produk')->where('id', $id)->first();
        $user = Auth::user();
        $kopQuery = DB::table('koperasi')->select('id', 'nama_koperasi')->orderBy('nama_koperasi');
        if ($user && ! $user->hasRole('superadmin')) {
            $kopQuery->where('id', $user->koperasi_id);
        }
        $koperasis = $kopQuery->get();

        return view('kategori_produk.edit', ['row' => $row, 'koperasis' => $koperasis]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'koperasi_id' => 'required|exists:koperasi,id',
            'nama_kategori' => 'required|string|max:100',
            'gambar' => 'nullable|image|mimes:jpeg,png,webp,avif|max:2048',
        ]);
        $user = Auth::user();
        $koperasiId = ($user && $user->hasRole('superadmin')) ? (int) $validated['koperasi_id'] : (int) ($user->koperasi_id ?? $validated['koperasi_id']);
        $update = [
            'koperasi_id' => $koperasiId,
            'nama_kategori' => $validated['nama_kategori'],
            'updated_at' => now(),
        ];
        if ($request->hasFile('gambar')) {
            $row = DB::table('kategori_produk')->where('id', $id)->first();
            $f = $request->file('gambar');
            $newPath = $f ? $f->store('kategori', 'public') : null;
            if ($row && isset($row->gambar) && $row->gambar && Storage::disk('public')->exists($row->gambar)) {
                try { Storage::disk('public')->delete($row->gambar); } catch (\Throwable $e) {}
            }
            $update['gambar'] = $newPath;
        }
        DB::table('kategori_produk')->where('id', $id)->update($update);

        return redirect()->route('kategori-produk.index')->with('status', 'Kategori diperbarui');
    }

    public function destroy($id)
    {
        DB::table('kategori_produk')->where('id', $id)->delete();

        return redirect()->route('kategori-produk.index')->with('status', 'Kategori dihapus');
    }

    public function show($id)
    {
        return redirect()->route('kategori-produk.edit', $id);
    }
}
