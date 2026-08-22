<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // users.role is filtered on every "find all admins" call (notifications
        // fan-out, admin bootstrap, password service) and the admin user listing
        // (role + orderByDesc(created_at)) — previously an unindexed full scan.
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'created_at'], 'users_role_created_at_idx');
        });

        // inventories.variant_id already has a unique index from the column's
        // ->unique() constraint (create_inventories_table); this non-unique
        // index added later duplicates it, doubling write-time index maintenance
        // for no lookup benefit.
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropIndex('inventories_variant_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_created_at_idx');
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->index('variant_id', 'inventories_variant_id_idx');
        });
    }
};
