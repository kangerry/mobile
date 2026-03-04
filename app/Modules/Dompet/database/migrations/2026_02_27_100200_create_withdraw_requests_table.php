<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('withdraw_requests')) {
            Schema::create('withdraw_requests', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('koperasi_id')->constrained('koperasi')->cascadeOnDelete();
                $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
                $table->foreignId('bank_account_id')->constrained('anggota_bank_accounts')->cascadeOnDelete();
                $table->decimal('amount', 15, 2);
                $table->string('status', 20)->default('pending');
                $table->string('external_id', 100)->nullable();
                $table->json('response_payload')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_requests');
    }
};
