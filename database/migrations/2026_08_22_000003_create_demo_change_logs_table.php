<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demo_admin_session_id')->constrained('demo_admin_sessions')->cascadeOnDelete();
            $table->string('model_type');
            $table->string('model_key');
            $table->enum('action', ['created', 'updated', 'deleted']);
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['demo_admin_session_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_change_logs');
    }
};
