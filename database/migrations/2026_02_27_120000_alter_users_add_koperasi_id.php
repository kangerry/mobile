<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasTable('koperasi')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'koperasi_id')) {
                    $table->foreignId('koperasi_id')->nullable()->after('id')->constrained('koperasi')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'koperasi_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['koperasi_id']);
                $table->dropColumn('koperasi_id');
            });
        }
    }
};
