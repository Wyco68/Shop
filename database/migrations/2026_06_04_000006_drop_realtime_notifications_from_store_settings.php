<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('store_settings', 'realtime_notifications_enabled')) {
            Schema::table('store_settings', function (Blueprint $table) {
                $table->dropColumn('realtime_notifications_enabled');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('store_settings', 'realtime_notifications_enabled')) {
            Schema::table('store_settings', function (Blueprint $table) {
                $table->boolean('realtime_notifications_enabled')->default(true)->after('logo_path');
            });
        }
    }
};
