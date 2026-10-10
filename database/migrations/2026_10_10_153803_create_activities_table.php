<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The append-only activity log: who did what, when, through which surface
     * and from where.
     *
     * `causer_id` and `impersonator_id` have no foreign key: the trigger
     * refuses every update, so a `nullOnDelete` could never run, and the names
     * are kept as a snapshot next to the ids. A deleted user keeps their
     * entries.
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('causer_id')->nullable()->index();
            $table->string('causer_name');
            $table->unsignedBigInteger('impersonator_id')->nullable()->index();
            $table->string('impersonator_name')->nullable();
            $table->string('source');
            $table->string('event')->index();
            $table->json('changes');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['subject_type', 'subject_id', 'created_at']);
        });

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION activities_immutable() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'The activity log is append-only: % on activities is not allowed', TG_OP;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER activities_immutable
                BEFORE UPDATE OR DELETE ON activities
                FOR EACH ROW EXECUTE FUNCTION activities_immutable();
            SQL);
    }

    /**
     * `migrate:fresh` drops tables but not functions, so the function is
     * removed here and `up()` uses CREATE OR REPLACE.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS activities_immutable ON activities');
        DB::unprepared('DROP FUNCTION IF EXISTS activities_immutable()');

        Schema::dropIfExists('activities');
    }
};
