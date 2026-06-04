<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('currency_locked')->default(false)->after('currency_position');
        });

        if (Schema::hasTable('store_settings')) {
            DB::table('store_settings')
                ->whereNotNull('store_name')
                ->where('store_name', '!=', '')
                ->update(['currency_locked' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn('currency_locked');
        });
    }
};
