<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('produk_makanan')) {
            Schema::create('produk_makanan', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('merchant_id')->constrained('merchant')->cascadeOnDelete();
                $table->string('nama_produk', 150);
                $table->text('deskripsi')->nullable();
                $table->decimal('harga', 15, 2);
                $table->boolean('status_tersedia')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('produk_makanan');
    }
};
