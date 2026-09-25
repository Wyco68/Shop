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

        // create_inventories_table chains ->unique() after ->constrained(), which
        // lands on the foreign key definition rather than the column, so no
        // unique index is ever created. inventories_variant_id_idx is then the
        // only index backing the foreign key and MySQL refuses to drop it
        // (error 1553). Add the intended one-row-per-variant unique index first;
        // it serves the foreign key and makes the non-unique index redundant.
        Schema::table('inventories', function (Blueprint $table) {
            $table->unique('variant_id', 'inventories_variant_id_unique');
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
            $table->dropUnique('inventories_variant_id_unique');
        });
    }
};
