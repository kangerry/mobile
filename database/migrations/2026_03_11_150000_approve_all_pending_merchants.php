<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        try {
            DB::table('merchant')
                ->where('status', 'pending')
                ->update([
                    'status' => 'aktif',
                    'updated_at' => now(),
                ]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        // no-op
    }
};

