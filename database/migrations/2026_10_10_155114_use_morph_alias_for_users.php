<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The morph map is enforced (see AppServiceProvider), so polymorphic
     * columns store the alias `user` instead of the class name. Roles and
     * permissions assigned before that still name the class; without this
     * they would no longer be found.
     */
    public function up(): void
    {
        $this->rename(User::class, 'user');
    }

    public function down(): void
    {
        $this->rename('user', User::class);
    }

    private function rename(string $from, string $to): void
    {
        foreach (['model_has_roles', 'model_has_permissions'] as $key) {
            DB::table(config("permission.table_names.{$key}"))
                ->where('model_type', $from)
                ->update(['model_type' => $to]);
        }
    }
};
