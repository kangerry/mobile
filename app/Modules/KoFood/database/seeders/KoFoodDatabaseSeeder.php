<?php

namespace Modules\KoFood\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KoFoodDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $koperasi = DB::table('koperasi')->where('kode_koperasi', 'KOP001')->first();
        if (! $koperasi) {
            return;
        }

        $merchant = DB::table('merchant')->where('koperasi_id', $koperasi->id)->where('nama_toko', 'Kedai Sederhana')->first();
        if (! $merchant) {
            $merchantId = DB::table('merchant')->insertGetId([
                'koperasi_id' => $koperasi->id,
                'nama_toko' => 'Kedai Sederhana',
                'nama_pemilik' => 'Budi',
                'alamat' => 'Jl. Melati No.2',
                'kota' => 'Bandung',
                'provinsi' => 'Jawa Barat',
                'latitude' => -6.89,
                'longitude' => 107.61,
                'aktif_delivery_toko' => true,
                'biaya_delivery_toko' => 10000,
                'aktif_delivery_kojek' => true,
                'radius_layanan_km' => 5,
                'status' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $merchant = DB::table('merchant')->where('id', $merchantId)->first();
        }

        $produk = DB::table('produk_makanan')->where('merchant_id', $merchant->id)->where('nama_produk', 'Nasi Goreng')->first();
        if (! $produk) {
            $produkId = DB::table('produk_makanan')->insertGetId([
                'merchant_id' => $merchant->id,
                'nama_produk' => 'Nasi Goreng',
                'deskripsi' => 'Nasi goreng special',
                'harga' => 20000,
                'status_tersedia' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $produk = DB::table('produk_makanan')->where('id', $produkId)->first();
        }
        $fotoCount = DB::table('produk_foto')->where('produk_id', $produk->id)->count();
        if ($fotoCount === 0) {
            DB::table('produk_foto')->insert([
                [
                    'produk_id' => $produk->id,
                    'url_foto' => 'https://picsum.photos/seed/kofood1/800/600',
                    'urutan' => 1,
                    'created_at' => now(),
                ],
                [
                    'produk_id' => $produk->id,
                    'url_foto' => 'https://picsum.photos/seed/kofood2/800/600',
                    'urutan' => 2,
                    'created_at' => now(),
                ],
            ]);
        }

        if (DB::table('delivery_toko_tarif')->where('koperasi_id', $koperasi->id)->count() === 0) {
            DB::table('delivery_toko_tarif')->insert([
                [
                    'koperasi_id' => $koperasi->id,
                    'merchant_id' => null,
                    'start_km' => 0,
                    'end_km' => 3,
                    'biaya_dasar' => 5000,
                    'biaya_per_km' => 2000,
                    'min_fare' => 8000,
                    'aktif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'koperasi_id' => $koperasi->id,
                    'merchant_id' => null,
                    'start_km' => 3.01,
                    'end_km' => 10,
                    'biaya_dasar' => 5000,
                    'biaya_per_km' => 2500,
                    'min_fare' => 12000,
                    'aktif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
