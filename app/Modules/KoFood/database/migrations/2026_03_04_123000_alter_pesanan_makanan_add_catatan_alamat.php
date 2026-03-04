<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pesanan_makanan')) {
            Schema::table('pesanan_makanan', function (Blueprint $table) {
                if (! Schema::hasColumn('pesanan_makanan', 'catatan_alamat')) {
                    $table->text('catatan_alamat')->nullable()->after('alamat_tujuan');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pesanan_makanan')) {
            Schema::table('pesanan_makanan', function (Blueprint $table) {
                if (Schema::hasColumn('pesanan_makanan', 'catatan_alamat')) {
                    $table->dropColumn('catatan_alamat');
                }
            });
        }
    }
};
