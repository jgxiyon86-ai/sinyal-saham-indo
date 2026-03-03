<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'client_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('client_code', 120)->nullable()->after('name');
            });
        }

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('client_code');
            });
        } catch (\Throwable) {
            // ignore when unique index already exists
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            try {
                $table->dropUnique(['client_code']);
            } catch (\Throwable) {
                // ignore when index is missing
            }
        });

        if (Schema::hasColumn('users', 'client_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('client_code');
            });
        }
    }
};
