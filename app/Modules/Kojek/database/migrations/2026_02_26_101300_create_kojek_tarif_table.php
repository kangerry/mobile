<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kojek_tarif')) {
            Schema::create('kojek_tarif', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('koperasi_id')->constrained('koperasi')->cascadeOnDelete();
                $table->decimal('start_km', 6, 2);
                $table->decimal('end_km', 6, 2);
                $table->decimal('biaya_dasar', 15, 2)->default(0);
                $table->decimal('biaya_per_km', 15, 2);
                $table->decimal('min_fare', 15, 2)->default(0);
                $table->boolean('aktif')->default(true);
                $table->timestamps();
            });
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE kojek_tarif ADD CONSTRAINT kojek_tarif_range_check CHECK (start_km >= 0 AND end_km > start_km)');
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kojek_tarif');
    }
};
