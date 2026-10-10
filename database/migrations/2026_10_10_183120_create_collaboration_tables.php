<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Working together on the fields of a record: who is editing which field
     * right now (`field_locks`), and the history of long texts
     * (`field_revisions`). The value itself stays in the record's own column.
     *
     * Revisions are append-only, like the activity log: a trigger refuses
     * every update and delete. Restoring one adds a new revision.
     */
    public function up(): void
    {
        Schema::create('field_locks', function (Blueprint $table): void {
            $table->id();
            $table->string('editable_type');
            $table->unsignedBigInteger('editable_id');
            $table->string('field');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('impersonator_id')->nullable();
            $table->string('source');
            // The browser tab holding the field; null for a tool.
            $table->string('client_id')->nullable();
            // The value when the session began, for the log of what it changed.
            $table->text('base_value')->nullable();
            $table->timestamp('claimed_at');
            $table->timestamp('heartbeat_at');
            $table->timestamp('last_edit_at');
            $table->timestamp('expires_at')->index();

            $table->unique(['editable_type', 'editable_id', 'field']);
        });

        Schema::create('field_revisions', function (Blueprint $table): void {
            $table->id();
            $table->string('editable_type');
            $table->unsignedBigInteger('editable_id');
            $table->string('field');
            $table->unsignedInteger('number');
            $table->text('content');
            $table->unsignedBigInteger('author_id')->nullable();
            $table->string('author_name');
            $table->unsignedBigInteger('impersonator_id')->nullable();
            $table->string('impersonator_name')->nullable();
            $table->string('source');
            $table->unsignedInteger('restored_from_number')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['editable_type', 'editable_id', 'field', 'number']);
        });

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION field_revisions_immutable() RETURNS trigger AS $$
            BEGIN
                IF current_setting('app.activity_privacy_maintenance', true) = 'on' THEN
                    RETURN COALESCE(NEW, OLD);
                END IF;

                RAISE EXCEPTION 'Field revisions are append-only: % on field_revisions is not allowed', TG_OP;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER field_revisions_immutable
                BEFORE UPDATE OR DELETE ON field_revisions
                FOR EACH ROW EXECUTE FUNCTION field_revisions_immutable();
            SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS field_revisions_immutable ON field_revisions');
        DB::unprepared('DROP FUNCTION IF EXISTS field_revisions_immutable()');

        Schema::dropIfExists('field_revisions');
        Schema::dropIfExists('field_locks');
    }
};
