<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32);
            $table->string('username')->nullable();
            $table->string('link', 2048)->nullable();
            $table->string('qr_path')->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['enabled', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_contacts');
    }
};
