<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doku_settings', function (Blueprint $table) {
            $table->id();
            $table->string('kode_koperasi')->unique();
            $table->string('env')->default('sandbox');
            $table->string('client_id');
            $table->string('secret_key');
            $table->string('api_key');
            $table->text('public_key');
            $table->string('base_url');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doku_settings');
    }
};
