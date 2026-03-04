<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('merchant')) {
            Schema::table('merchant', function (Blueprint $table) {
                if (! Schema::hasColumn('merchant', 'anggota_id')) {
                    $table->foreignId('anggota_id')->nullable()->constrained('anggota')->nullOnDelete()->after('koperasi_id');
                }
                if (! Schema::hasColumn('merchant', 'deskripsi')) {
                    $table->text('deskripsi')->nullable()->after('nama_toko');
                }
                if (! Schema::hasColumn('merchant', 'nib')) {
                    $table->string('nib', 50)->nullable()->after('telepon');
                }
                if (! Schema::hasColumn('merchant', 'pirt')) {
                    $table->string('pirt', 50)->nullable()->after('nib');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('merchant')) {
            Schema::table('merchant', function (Blueprint $table) {
                if (Schema::hasColumn('merchant', 'anggota_id')) {
                    $table->dropConstrainedForeignId('anggota_id');
                }
                if (Schema::hasColumn('merchant', 'deskripsi')) {
                    $table->dropColumn('deskripsi');
                }
                if (Schema::hasColumn('merchant', 'nib')) {
                    $table->dropColumn('nib');
                }
                if (Schema::hasColumn('merchant', 'pirt')) {
                    $table->dropColumn('pirt');
                }
            });
        }
    }
};
