<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('driver')) {
            Schema::create('driver', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('koperasi_id')->constrained('koperasi')->cascadeOnDelete();
                $table->string('nama_driver', 150);
                $table->string('email', 150)->nullable();
                $table->string('telepon', 20)->nullable();
                $table->string('password', 255)->nullable();
                $table->string('jenis_kendaraan', 20)->nullable();
                $table->string('plat_nomor', 20)->nullable();
                $table->string('nomor_sim', 50)->nullable();
                $table->boolean('terverifikasi')->default(false);
                $table->boolean('status_online')->default(false);
                $table->decimal('latitude_terakhir', 10, 8)->nullable();
                $table->decimal('longitude_terakhir', 11, 8)->nullable();
                $table->decimal('rating', 3, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('driver');
    }
};
