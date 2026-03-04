<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('produk_foto')) {
            Schema::create('produk_foto', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('produk_id')->constrained('produk_makanan')->cascadeOnDelete();
                $table->string('url_foto', 255);
                $table->smallInteger('urutan');
                $table->timestamp('created_at')->nullable();
            });

            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE produk_foto ADD CONSTRAINT produk_foto_urutan_check CHECK (urutan BETWEEN 1 AND 5)');
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('produk_foto');
    }
};
