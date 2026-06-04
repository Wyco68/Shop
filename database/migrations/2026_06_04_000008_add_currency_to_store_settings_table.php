<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->string('currency_code', 3)->default('USD')->after('logo_path');
            $table->string('currency_symbol', 8)->default('$')->after('currency_code');
            $table->string('currency_position', 10)->default('before')->after('currency_symbol');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn(['currency_code', 'currency_symbol', 'currency_position']);
        });
    }
};
