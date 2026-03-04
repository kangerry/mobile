<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pesanan_makanan')) {
            Schema::create('pesanan_makanan', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('koperasi_id')->constrained('koperasi');
                $table->string('nomor_pesanan', 50)->unique();
                $table->foreignId('anggota_id')->constrained('anggota');
                $table->foreignId('merchant_id')->constrained('merchant');
                $table->string('tipe_pengiriman', 20)->nullable();
                $table->foreignId('driver_id')->nullable()->constrained('driver');
                $table->decimal('subtotal', 15, 2)->nullable();
                $table->decimal('biaya_pengiriman', 15, 2)->nullable();
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
        Schema::dropIfExists('pesanan_makanan');
    }
};
