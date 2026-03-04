<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dompet')) {
            Schema::create('dompet', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('koperasi_id')->constrained('koperasi');
                $table->foreignId('anggota_id')->constrained('anggota');
                $table->decimal('saldo', 15, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dompet');
    }
};
