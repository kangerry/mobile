<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('detail_pesanan_makanan')) {
            Schema::create('detail_pesanan_makanan', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('pesanan_makanan_id')->constrained('pesanan_makanan')->cascadeOnDelete();
                $table->foreignId('produk_id')->constrained('produk_makanan');
                $table->integer('jumlah');
                $table->decimal('harga_satuan', 15, 2)->nullable();
                $table->decimal('subtotal', 15, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_pesanan_makanan');
    }
};
