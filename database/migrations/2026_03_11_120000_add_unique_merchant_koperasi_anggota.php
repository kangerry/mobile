<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            $dups = DB::select("
                SELECT koperasi_id, anggota_id, COUNT(*) AS c
                FROM merchant
                WHERE anggota_id IS NOT NULL
                GROUP BY koperasi_id, anggota_id
                HAVING c > 1
            ");
            foreach ($dups as $d) {
                $ids = DB::table('merchant')
                    ->where('koperasi_id', $d->koperasi_id)
                    ->where('anggota_id', $d->anggota_id)
                    ->orderBy('id')
                    ->pluck('id')
                    ->toArray();
                if (count($ids) > 1) {
                    array_shift($ids);
                    DB::table('merchant')->whereIn('id', $ids)->delete();
                }
            }
        } catch (\Throwable $e) {
        }
        Schema::table('merchant', function (Blueprint $table) {
            try {
                $table->unique(['koperasi_id', 'anggota_id'], 'merchant_koperasi_anggota_unique');
            } catch (\Throwable $e) {
            }
        });
    }

    public function down(): void
    {
        Schema::table('merchant', function (Blueprint $table) {
            try {
                $table->dropUnique('merchant_koperasi_anggota_unique');
            } catch (\Throwable $e) {
            }
        });
    }
};

