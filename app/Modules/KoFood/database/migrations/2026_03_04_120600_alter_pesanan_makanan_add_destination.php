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
                if (! Schema::hasColumn('pesanan_makanan', 'alamat_tujuan')) {
                    $table->text('alamat_tujuan')->nullable()->after('tipe_pengiriman');
                }
                if (! Schema::hasColumn('pesanan_makanan', 'latitude_tujuan')) {
                    $table->decimal('latitude_tujuan', 10, 8)->nullable()->after('alamat_tujuan');
                }
                if (! Schema::hasColumn('pesanan_makanan', 'longitude_tujuan')) {
                    $table->decimal('longitude_tujuan', 11, 8)->nullable()->after('latitude_tujuan');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pesanan_makanan')) {
            Schema::table('pesanan_makanan', function (Blueprint $table) {
                if (Schema::hasColumn('pesanan_makanan', 'longitude_tujuan')) {
                    $table->dropColumn('longitude_tujuan');
                }
                if (Schema::hasColumn('pesanan_makanan', 'latitude_tujuan')) {
                    $table->dropColumn('latitude_tujuan');
                }
                if (Schema::hasColumn('pesanan_makanan', 'alamat_tujuan')) {
                    $table->dropColumn('alamat_tujuan');
                }
            });
        }
    }
};
