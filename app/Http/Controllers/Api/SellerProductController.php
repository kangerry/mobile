<?php

namespace App\Http\Controllers\Api;

use App\Models\Anggota;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SellerProductController extends BaseController
{
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            if (! $user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            $merchantId = null;
            if ($user instanceof Merchant) {
                $merchantId = (int) $user->id;
            } elseif ($user instanceof Anggota) {
                $kopId = (int) $request->attributes->get('koperasi_id');
                $m = DB::table('merchant')
                    ->where('anggota_id', $user->id)
                    ->where('koperasi_id', $kopId)
                    ->where('status', 'aktif')
                    ->first();
                if (! $m) {
                    return response()->json(['message' => 'Belum ada merchant aktif untuk anggota ini'], 403);
                }
                $merchantId = (int) $m->id;
            } else {
                return response()->json(['message' => 'Hanya seller yang dapat mengelola produk'], 403);
            }
            $v = $request->validate([
                'nama_produk' => ['required', 'string', 'max:150'],
                'deskripsi' => ['nullable', 'string'],
                'harga' => ['required'],
                'kategori_id' => ['required', 'integer'],
            ]);
            $harga = (float) $v['harga'];
            if ($harga <= 0) {
                throw ValidationException::withMessages(['harga' => 'Harga harus lebih dari 0']);
            }
            $kopIdForKategori = (int) $request->attributes->get('koperasi_id');
            $kategoriId = (int) $v['kategori_id'];
            $kategoriValid = DB::table('kategori_produk')
                ->where('id', $kategoriId)
                ->where('koperasi_id', $kopIdForKategori)
                ->exists();
            if (! $kategoriValid) {
                throw ValidationException::withMessages(['kategori_id' => 'Kategori tidak valid untuk koperasi aktif']);
            }
            $id = DB::table('produk_makanan')->insertGetId([
                'merchant_id' => $merchantId,
                'nama_produk' => $v['nama_produk'],
                'deskripsi' => $v['deskripsi'] ?? null,
                'harga' => $harga,
                'kategori_id' => $kategoriId,
                'status_tersedia' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Log::info('Produk dibuat', ['produk_id' => $id, 'merchant_id' => $merchantId, 'user_id' => $user->id]);
            return response()->json(['id' => $id], 201);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat produk', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Terjadi kesalahan saat menyimpan produk'], 500);
        }
    }

    public function uploadPhoto(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (! $user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            $merchantId = null;
            if ($user instanceof Merchant) {
                $merchantId = (int) $user->id;
            } elseif ($user instanceof Anggota) {
                $kopId = (int) $request->attributes->get('koperasi_id');
                $m = DB::table('merchant')
                    ->where('anggota_id', $user->id)
                    ->where('koperasi_id', $kopId)
                    ->where('status', 'aktif')
                    ->first();
                if (! $m) {
                    return response()->json(['message' => 'Belum ada merchant aktif untuk anggota ini'], 403);
                }
                $merchantId = (int) $m->id;
            } else {
                return response()->json(['message' => 'Hanya seller yang dapat mengelola produk'], 403);
            }
            $prod = DB::table('produk_makanan')->where('id', $id)->first();
            if (! $prod || (int) $prod->merchant_id !== $merchantId) {
                return response()->json(['message' => 'Produk tidak ditemukan'], 404);
            }
            $request->validate([
                'file' => ['required', 'file', 'image', 'max:5120'],
            ]);
            $file = $request->file('file');
            $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('produk', $filename, ['disk' => 'public']);
            $next = (int) (DB::table('produk_foto')->where('produk_id', $id)->max('urutan') ?? 0) + 1;
            if ($next > 5) {
                return response()->json(['message' => 'Maksimal 5 foto per produk'], 422);
            }
            DB::table('produk_foto')->insert([
                'produk_id' => (int) $id,
                'url_foto' => $path,
                'urutan' => $next,
                'created_at' => now(),
            ]);
            $url = url('storage/'.$path);
            Log::info('Foto produk diunggah', ['produk_id' => (int) $id, 'merchant_id' => $merchantId, 'path' => $path, 'urutan' => $next]);
            return response()->json(['url' => $url, 'urutan' => $next]);
        } catch (\Throwable $e) {
            Log::error('Gagal upload foto produk', [
                'produk_id' => (int) $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Terjadi kesalahan saat upload foto'], 500);
        }
    }
}
