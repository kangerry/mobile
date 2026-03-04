<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('setup_gateway')) {
            Schema::create('setup_gateway', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('koperasi_id')->constrained('koperasi')->cascadeOnDelete();
                $table->string('nama_gateway', 100);
                $table->string('client_id', 255)->nullable();
                $table->string('secret_key', 255)->nullable();
                $table->string('api_key', 255)->nullable();
                $table->string('public_key', 255)->nullable();
                $table->string('base_url', 255)->nullable();
                $table->string('mode', 20)->default('sandbox');
                $table->boolean('status_aktif')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('setup_gateway');
    }
};
