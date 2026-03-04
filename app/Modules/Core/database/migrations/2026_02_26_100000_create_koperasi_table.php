<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('koperasi')) {
            Schema::create('koperasi', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('kode_koperasi', 50)->unique();
                $table->string('nama_koperasi', 150);
                $table->string('logo', 255)->nullable();
                $table->text('alamat');
                $table->string('provinsi', 100);
                $table->string('kab_kota', 100);
                $table->string('kecamatan', 100);
                $table->string('desa', 100);
                $table->decimal('latitude', 10, 8);
                $table->decimal('longitude', 11, 8);
                $table->boolean('status_aktif')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('koperasi');
    }
};
