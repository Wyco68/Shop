<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_active', 'created_at'], 'products_active_created_index');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index(['is_active', 'slug'], 'categories_active_slug_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_active_created_index');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_active_slug_index');
        });
    }
};
