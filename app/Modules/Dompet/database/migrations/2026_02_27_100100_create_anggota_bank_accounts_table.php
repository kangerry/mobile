<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('anggota_bank_accounts')) {
            Schema::create('anggota_bank_accounts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('koperasi_id')->constrained('koperasi')->cascadeOnDelete();
                $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
                $table->string('bank_code', 12);
                $table->string('bank_name', 100);
                $table->string('account_number', 50);
                $table->string('account_holder', 100);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('anggota_bank_accounts');
    }
};
