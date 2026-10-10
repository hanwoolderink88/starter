<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The activity log stays append-only, with one exception: privacy
     * maintenance (anonymizing a deleted user, clearing network details, the
     * retention term) may update or delete entries. It marks its own
     * transaction with `SET LOCAL app.activity_privacy_maintenance = 'on'`;
     * the setting ends with the transaction, so nothing else ever gets through.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION activities_immutable() RETURNS trigger AS $$
            BEGIN
                IF current_setting('app.activity_privacy_maintenance', true) = 'on' THEN
                    RETURN COALESCE(NEW, OLD);
                END IF;

                RAISE EXCEPTION 'The activity log is append-only: % on activities is not allowed', TG_OP;
            END;
            $$ LANGUAGE plpgsql;
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION activities_immutable() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'The activity log is append-only: % on activities is not allowed', TG_OP;
            END;
            $$ LANGUAGE plpgsql;
            SQL);
    }
};
