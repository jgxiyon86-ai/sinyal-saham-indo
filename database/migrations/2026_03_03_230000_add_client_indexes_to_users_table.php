<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            try {
                $table->index(['role', 'is_active'], 'users_role_is_active_idx');
            } catch (\Throwable) {
                // ignore if exists
            }

            try {
                $table->index(['role', 'tier_id'], 'users_role_tier_id_idx');
            } catch (\Throwable) {
                // ignore if exists
            }

            try {
                $table->index(['role', 'client_code'], 'users_role_client_code_idx');
            } catch (\Throwable) {
                // ignore if exists
            }

            try {
                $table->index(['role', 'whatsapp_number'], 'users_role_wa_idx');
            } catch (\Throwable) {
                // ignore if exists
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            try {
                $table->dropIndex('users_role_is_active_idx');
            } catch (\Throwable) {
                // ignore if missing
            }

            try {
                $table->dropIndex('users_role_tier_id_idx');
            } catch (\Throwable) {
                // ignore if missing
            }

            try {
                $table->dropIndex('users_role_client_code_idx');
            } catch (\Throwable) {
                // ignore if missing
            }

            try {
                $table->dropIndex('users_role_wa_idx');
            } catch (\Throwable) {
                // ignore if missing
            }
        });
    }
};
