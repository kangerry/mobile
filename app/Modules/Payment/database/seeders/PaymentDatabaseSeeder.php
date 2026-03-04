<?php

namespace Modules\Payment\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentDatabaseSeeder extends Seeder
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

        if (! DB::table('setup_gateway')->where('koperasi_id', $koperasi->id)->exists()) {
            DB::table('setup_gateway')->insert([
                'koperasi_id' => $koperasi->id,
                'nama_gateway' => 'Midtrans',
                'mode' => 'sandbox',
                'status_aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
