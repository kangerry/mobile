<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('driver_device_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('driver_id')->index();
            $table->string('token', 255)->index();
            $table->string('platform', 50)->nullable();
            $table->timestamps();
            $table->unique(['driver_id', 'token']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('driver_device_tokens');
    }
};

