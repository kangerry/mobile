<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pesanan_chat', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('koperasi_id')->index();
            $table->unsignedBigInteger('pesanan_id')->index();
            $table->string('sender_type', 20); // anggota|merchant|driver
            $table->unsignedBigInteger('sender_id');
            $table->text('message')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('pesanan_chat');
    }
};

