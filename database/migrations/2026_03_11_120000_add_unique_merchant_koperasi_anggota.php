<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public bool $withinTransaction = false;
    public function up(): void
    {
        $dups = DB::select("
            SELECT koperasi_id, anggota_id, COUNT(*) AS c
            FROM merchant
            WHERE anggota_id IS NOT NULL
            GROUP BY koperasi_id, anggota_id
            HAVING COUNT(*) > 1
        ");
        if (! empty($dups)) {
            return;
        }
        try {
            DB::statement('ALTER TABLE "merchant" ADD CONSTRAINT "merchant_koperasi_anggota_unique" UNIQUE ("koperasi_id","anggota_id")');
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE "merchant" DROP CONSTRAINT IF EXISTS "merchant_koperasi_anggota_unique"');
        } catch (\Throwable $e) {
        }
    }
};
