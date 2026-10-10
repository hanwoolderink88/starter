<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The language a user chose for the interface and for the mails they get.
     * Null follows the browser, then the application default.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('locale', 5)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('locale');
        });
    }
};
