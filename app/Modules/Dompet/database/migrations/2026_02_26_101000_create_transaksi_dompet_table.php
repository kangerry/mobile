<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transaksi_dompet')) {
            Schema::create('transaksi_dompet', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('koperasi_id')->constrained('koperasi');
                $table->foreignId('dompet_id')->constrained('dompet');
                $table->string('jenis', 20)->nullable();
                $table->decimal('jumlah', 15, 2)->nullable();
                $table->string('referensi_tipe', 30)->nullable();
                $table->bigInteger('referensi_id')->nullable();
                $table->text('keterangan')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_dompet');
    }
};
