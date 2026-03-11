<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doku_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('doku_settings', 'allow_sandbox_simulation')) {
                $table->boolean('allow_sandbox_simulation')->default(true)->after('base_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('doku_settings', function (Blueprint $table) {
            if (Schema::hasColumn('doku_settings', 'allow_sandbox_simulation')) {
                $table->dropColumn('allow_sandbox_simulation');
            }
        });
    }
};
