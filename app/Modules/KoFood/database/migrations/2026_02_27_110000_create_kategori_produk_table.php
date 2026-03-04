<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kategori_produk')) {
            Schema::create('kategori_produk', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('koperasi_id')->constrained('koperasi')->cascadeOnDelete();
                $table->string('nama_kategori', 100);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_produk');
    }
};
