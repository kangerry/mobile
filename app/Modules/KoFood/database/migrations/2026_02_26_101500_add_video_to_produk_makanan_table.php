<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('produk_makanan')) {
            Schema::table('produk_makanan', function (Blueprint $table) {
                if (! Schema::hasColumn('produk_makanan', 'video_url')) {
                    $table->string('video_url', 255)->nullable()->after('deskripsi');
                }
                if (! Schema::hasColumn('produk_makanan', 'video_path')) {
                    $table->string('video_path', 255)->nullable()->after('video_url');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('produk_makanan')) {
            Schema::table('produk_makanan', function (Blueprint $table) {
                if (Schema::hasColumn('produk_makanan', 'video_path')) {
                    $table->dropColumn('video_path');
                }
                if (Schema::hasColumn('produk_makanan', 'video_url')) {
                    $table->dropColumn('video_url');
                }
            });
        }
    }
};
