<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deleting a user is a soft delete: they can no longer log in, and can be
     * restored until they are anonymized (`anonymized_at`). The email address
     * only has to be unique among users who are not deleted, so a deleted
     * user's address can be invited again.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->softDeletes();
            $table->timestamp('anonymized_at')->nullable();
            $table->dropUnique(['email']);
        });

        DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_email_unique');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['deleted_at', 'anonymized_at']);
            $table->unique('email');
        });
    }
};
