<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('doku_settings')) {
            Schema::table('doku_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('doku_settings', 'private_key')) {
                    $table->text('private_key')->nullable()->after('api_key');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('doku_settings')) {
            Schema::table('doku_settings', function (Blueprint $table) {
                if (Schema::hasColumn('doku_settings', 'private_key')) {
                    $table->dropColumn('private_key');
                }
            });
        }
    }
};

