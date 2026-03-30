<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pesanan_makanan')) {
            Schema::table('pesanan_makanan', function (Blueprint $table) {
                if (! Schema::hasColumn('pesanan_makanan', 'offer_expires_at')) {
                    $table->timestamp('offer_expires_at')->nullable()->index();
                }
                if (! Schema::hasColumn('pesanan_makanan', 'offer_round')) {
                    $table->unsignedSmallInteger('offer_round')->default(0)->index();
                }
            });
        }
    }
    public function down(): void
    {
        if (Schema::hasTable('pesanan_makanan')) {
            Schema::table('pesanan_makanan', function (Blueprint $table) {
                if (Schema::hasColumn('pesanan_makanan', 'offer_expires_at')) {
                    $table->dropColumn('offer_expires_at');
                }
                if (Schema::hasColumn('pesanan_makanan', 'offer_round')) {
                    $table->dropColumn('offer_round');
                }
            });
        }
    }
};

