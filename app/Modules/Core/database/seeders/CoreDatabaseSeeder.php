<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoreDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! DB::table('koperasi')->where('kode_koperasi', 'KOP001')->exists()) {
            $koperasiId = DB::table('koperasi')->insertGetId([
                'kode_koperasi' => 'KOP001',
                'nama_koperasi' => 'Koperasi Maju',
                'alamat' => 'Jl. Mawar No.1',
                'provinsi' => 'Jawa Barat',
                'kab_kota' => 'Bandung',
                'kecamatan' => 'Coblong',
                'desa' => 'Dago',
                'latitude' => -6.89000000,
                'longitude' => 107.61000000,
                'status_aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('anggota')->insert([
                'koperasi_id' => $koperasiId,
                'nomor_anggota' => 'AG001',
                'nama_anggota' => 'Andi',
                'email' => 'andi@example.com',
                'telepon' => '0812000001',
                'status' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
