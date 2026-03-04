<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('merchant')) {
            Schema::create('merchant', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('koperasi_id')->constrained('koperasi')->cascadeOnDelete();
                $table->string('nama_toko', 150);
                $table->string('nama_pemilik', 150)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('telepon', 20)->nullable();
                $table->string('password', 255)->nullable();
                $table->text('alamat');
                $table->string('kota', 100)->nullable();
                $table->string('provinsi', 100)->nullable();
                $table->decimal('latitude', 10, 8);
                $table->decimal('longitude', 11, 8);
                $table->time('jam_buka')->nullable();
                $table->time('jam_tutup')->nullable();
                $table->boolean('aktif_delivery_toko')->default(true);
                $table->decimal('biaya_delivery_toko', 15, 2)->default(0);
                $table->boolean('aktif_delivery_kojek')->default(true);
                $table->decimal('radius_layanan_km', 5, 2)->default(5);
                $table->string('status', 20)->default('aktif');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant');
    }
};
