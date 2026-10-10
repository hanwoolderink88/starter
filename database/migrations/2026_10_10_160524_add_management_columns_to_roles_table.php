<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Roles become manageable: a description, an active flag (an inactive
     * role grants nothing but keeps its assignments) and a protected flag for
     * the one administrator role that can be neither deleted nor deactivated.
     *
     * The fixed `super-admin` and `user` roles of before become the protected
     * Administrator and an ordinary User role, so existing assignments keep
     * working.
     */
    public function up(): void
    {
        $table = config('permission.table_names.roles');

        Schema::table($table, function (Blueprint $table): void {
            $table->text('description')->nullable()->after('guard_name');
            $table->boolean('is_active')->default(true)->after('description');
            $table->boolean('is_protected')->default(false)->after('is_active');
        });

        DB::statement("CREATE UNIQUE INDEX roles_is_protected_unique ON {$table} (is_protected) WHERE is_protected");

        DB::table($table)->where('name', 'super-admin')->update([
            'name' => 'Administrator',
            'description' => 'Manages users, roles and the application.',
            'is_protected' => true,
        ]);

        DB::table($table)->where('name', 'user')->update([
            'name' => 'User',
            'description' => 'A regular user of the application.',
        ]);
    }

    public function down(): void
    {
        $table = config('permission.table_names.roles');

        DB::table($table)->where('is_protected', true)->update(['name' => 'super-admin']);
        DB::table($table)->where('name', 'User')->update(['name' => 'user']);

        DB::statement('DROP INDEX IF EXISTS roles_is_protected_unique');

        Schema::table($table, function (Blueprint $table): void {
            $table->dropColumn(['description', 'is_active', 'is_protected']);
        });
    }
};
