<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('anggota')) {
            Schema::create('anggota', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('koperasi_id')->constrained('koperasi')->cascadeOnDelete();
                $table->string('nomor_anggota', 50)->unique();
                $table->string('nama_anggota', 150);
                $table->string('email', 150);
                $table->string('telepon', 20);
                $table->string('password', 255)->nullable();
                $table->string('login_google_id', 150)->nullable();
                $table->string('status', 20)->default('aktif');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('anggota');
    }
};
