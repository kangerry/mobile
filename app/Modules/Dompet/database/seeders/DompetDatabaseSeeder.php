<?php

namespace Modules\Dompet\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DompetDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $koperasi = DB::table('koperasi')->where('kode_koperasi', 'KOP001')->first();
        $anggota = DB::table('anggota')->where('nomor_anggota', 'AG001')->first();
        if (! $koperasi || ! $anggota) {
            return;
        }
        if (! DB::table('dompet')->where('anggota_id', $anggota->id)->exists()) {
            DB::table('dompet')->insert([
                'koperasi_id' => $koperasi->id,
                'anggota_id' => $anggota->id,
                'saldo' => 0,
            ]);
        }
    }
}
