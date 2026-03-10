<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kategori_produk')) {
            Schema::table('kategori_produk', function (Blueprint $table) {
                if (! Schema::hasColumn('kategori_produk', 'gambar')) {
                    $table->string('gambar', 255)->nullable()->after('nama_kategori');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('kategori_produk')) {
            Schema::table('kategori_produk', function (Blueprint $table) {
                if (Schema::hasColumn('kategori_produk', 'gambar')) {
                    $table->dropColumn('gambar');
                }
            });
        }
    }
};

