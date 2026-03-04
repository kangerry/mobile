<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transaksi_gateway')) {
            Schema::create('transaksi_gateway', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('koperasi_id')->constrained('koperasi');
                $table->foreignId('gateway_id')->constrained('setup_gateway');
                $table->string('tipe_transaksi', 30)->nullable();
                $table->bigInteger('referensi_id');
                $table->string('nomor_invoice', 100)->nullable();
                $table->string('external_id', 100)->nullable();
                $table->decimal('jumlah', 15, 2)->nullable();
                $table->json('response_payload')->nullable();
                $table->string('status', 30)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_gateway');
    }
};
