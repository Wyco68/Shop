<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('type', 20)->default('bank')->after('code');
            // TEXT (not JSON): Laravel encrypted:array stores ciphertext, not JSON documents.
            $table->text('config')->nullable()->after('instructions');
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn(['type', 'config']);
        });
    }
};
