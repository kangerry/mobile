<?php

namespace Modules\Kojek\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KojekDatabaseSeeder extends Seeder
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

        DB::table('driver')->insert([
            'koperasi_id' => $koperasi->id,
            'nama_driver' => 'Ujang',
            'email' => 'ujang@example.com',
            'telepon' => '0812000002',
            'jenis_kendaraan' => 'Motor',
            'plat_nomor' => 'D 1234 UJ',
            'terverifikasi' => true,
            'status_online' => true,
            'rating' => 4.8,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (DB::table('kojek_tarif')->where('koperasi_id', $koperasi->id)->count() === 0) {
            DB::table('kojek_tarif')->insert([
                [
                    'koperasi_id' => $koperasi->id,
                    'start_km' => 0,
                    'end_km' => 3,
                    'biaya_dasar' => 8000,
                    'biaya_per_km' => 2500,
                    'min_fare' => 10000,
                    'aktif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'koperasi_id' => $koperasi->id,
                    'start_km' => 3.01,
                    'end_km' => 20,
                    'biaya_dasar' => 8000,
                    'biaya_per_km' => 3000,
                    'min_fare' => 12000,
                    'aktif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
