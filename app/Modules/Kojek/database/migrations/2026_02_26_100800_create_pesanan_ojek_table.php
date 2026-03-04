<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pesanan_ojek')) {
            Schema::create('pesanan_ojek', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('koperasi_id')->constrained('koperasi');
                $table->string('nomor_pesanan', 50)->unique();
                $table->foreignId('anggota_id')->constrained('anggota');
                $table->foreignId('driver_id')->nullable()->constrained('driver');
                $table->text('alamat_jemput')->nullable();
                $table->decimal('latitude_jemput', 10, 8)->nullable();
                $table->decimal('longitude_jemput', 11, 8)->nullable();
                $table->text('alamat_tujuan')->nullable();
                $table->decimal('latitude_tujuan', 10, 8)->nullable();
                $table->decimal('longitude_tujuan', 11, 8)->nullable();
                $table->decimal('jarak_km', 6, 2)->nullable();
                $table->decimal('biaya_dasar', 15, 2)->nullable();
                $table->decimal('biaya_jarak', 15, 2)->nullable();
                $table->decimal('biaya_platform', 15, 2)->nullable();
                $table->decimal('total_bayar', 15, 2)->nullable();
                $table->string('jenis_pembayaran', 30)->nullable();
                $table->string('status_pembayaran', 30)->nullable();
                $table->string('referensi_pembayaran', 100)->nullable();
                $table->string('status', 30)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan_ojek');
    }
};
