<?php

namespace App\Http\Controllers\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

class ProdukController extends BaseController
{
    public function index()
    {
        $items = DB::table('produk_makanan')
            ->join('merchant', 'produk_makanan.merchant_id', '=', 'merchant.id')
            ->select(
                'produk_makanan.*',
                'merchant.nama_toko',
                'merchant.koperasi_id',
            )
            ->selectSub(
                DB::table('produk_foto')
                    ->select('url_foto')
                    ->whereColumn('produk_foto.produk_id', 'produk_makanan.id')
                    ->orderBy('urutan')
                    ->limit(1),
                'foto_utama'
            )
            ->orderByDesc('produk_makanan.id')
            ->get();

        return view('produk.index', compact('items'));
    }

    public function create()
    {
        $merchants = DB::table('merchant')->select('id', 'nama_toko')->orderBy('nama_toko')->get();
        $categories = DB::table('kategori_produk')->select('id', 'nama_kategori')->orderBy('nama_kategori')->get();

        return view('produk.create', compact('merchants', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchant_id' => 'required|exists:merchant,id',
            'kategori_id' => 'nullable|exists:kategori_produk,id',
            'nama' => 'required|string|max:150',
            'harga' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'foto' => 'nullable|array|max:5',
            'foto.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'video_file' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska|max:51200',
            'video_url' => 'nullable|url|max:255',
        ]);

        $id = DB::table('produk_makanan')->insertGetId([
            'merchant_id' => (int) $validated['merchant_id'],
            'kategori_id' => isset($validated['kategori_id']) ? (int) $validated['kategori_id'] : null,
            'nama_produk' => $validated['nama'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'harga' => $validated['harga'],
            'video_url' => $validated['video_url'] ?? null,
            'status_tersedia' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->hasFile('video_file')) {
            $path = $request->file('video_file')->store('produk/video', 'public');
            DB::table('produk_makanan')->where('id', $id)->update(['video_path' => $path]);
        }

        if ($request->hasFile('foto')) {
            $files = $request->file('foto');
            $urutan = 1;
            foreach ($files as $file) {
                if ($urutan > 5) {
                    break;
                }
                $path = $file->store('produk/foto', 'public');
                DB::table('produk_foto')->insert([
                    'produk_id' => $id,
                    'url_foto' => $path,
                    'urutan' => $urutan,
                    'created_at' => now(),
                ]);
                $urutan++;
            }
        }

        return redirect()->route('produk.edit', $id)->with('status', 'Produk tersimpan');
    }

    public function edit($id)
    {
        $produk = DB::table('produk_makanan')->where('id', $id)->first();
        $fotos = DB::table('produk_foto')->where('produk_id', $id)->orderBy('urutan')->get();
        $merchants = DB::table('merchant')->select('id', 'nama_toko')->orderBy('nama_toko')->get();
        $categories = DB::table('kategori_produk')->select('id', 'nama_kategori')->orderBy('nama_kategori')->get();

        return view('produk.edit', compact('produk', 'fotos', 'merchants', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'merchant_id' => 'required|exists:merchant,id',
            'kategori_id' => 'nullable|exists:kategori_produk,id',
            'nama' => 'required|string|max:150',
            'harga' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'foto' => 'nullable|array|max:5',
            'foto.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'video_file' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska|max:51200',
            'video_url' => 'nullable|url|max:255',
        ]);

        DB::table('produk_makanan')->where('id', $id)->update([
            'merchant_id' => (int) $validated['merchant_id'],
            'kategori_id' => isset($validated['kategori_id']) ? (int) $validated['kategori_id'] : null,
            'nama_produk' => $validated['nama'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'harga' => $validated['harga'],
            'video_url' => $validated['video_url'] ?? null,
            'updated_at' => now(),
        ]);

        if ($request->hasFile('video_file')) {
            $path = $request->file('video_file')->store('produk/video', 'public');
            DB::table('produk_makanan')->where('id', $id)->update(['video_path' => $path]);
        }

        if ($request->hasFile('foto')) {
            $countExisting = (int) DB::table('produk_foto')->where('produk_id', $id)->count();
            $next = $countExisting + 1;
            foreach ($request->file('foto') as $file) {
                if ($next > 5) {
                    break;
                }
                $path = $file->store('produk/foto', 'public');
                DB::table('produk_foto')->insert([
                    'produk_id' => $id,
                    'url_foto' => $path,
                    'urutan' => $next,
                    'created_at' => now(),
                ]);
                $next++;
            }
        }

        return redirect()->route('produk.edit', $id)->with('status', 'Produk diperbarui');
    }

    public function destroy($id)
    {
        DB::table('produk_makanan')->where('id', $id)->delete();

        return redirect()->route('produk.index')->with('status', 'Produk dihapus');
    }

    public function show($id)
    {
        return redirect()->route('produk.edit', $id);
    }
}
