<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('merchant') && ! Schema::hasColumn('merchant', 'banner')) {
            Schema::table('merchant', function (Blueprint $table) {
                $table->string('banner')->nullable()->after('provinsi');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('merchant') && Schema::hasColumn('merchant', 'banner')) {
            Schema::table('merchant', function (Blueprint $table) {
                $table->dropColumn('banner');
            });
        }
    }
};
