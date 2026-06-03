<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payment_methods', 'config')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE payment_methods MODIFY config TEXT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE payment_methods ALTER COLUMN config TYPE TEXT USING config::text');
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('payment_methods', 'config')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE payment_methods MODIFY config JSON NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE payment_methods ALTER COLUMN config TYPE JSON USING config::json');
        }
    }
};
