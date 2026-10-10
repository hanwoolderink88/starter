<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optimistic locking: every save of a user or a role raises `version`, and
     * a save based on an older version is refused instead of silently
     * overwriting someone else's change. `updated_at` cannot do this: two
     * saves within the same second leave it unchanged.
     */
    public function up(): void
    {
        foreach (['users', config('permission.table_names.roles')] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->unsignedInteger('version')->default(1);
            });
        }
    }

    public function down(): void
    {
        foreach (['users', config('permission.table_names.roles')] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropColumn('version');
            });
        }
    }
};
