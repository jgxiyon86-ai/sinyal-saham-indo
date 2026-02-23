<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signals', function (Blueprint $table) {
            $table->timestamp('wa_blasted_at')->nullable()->after('push_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('signals', function (Blueprint $table) {
            $table->dropColumn('wa_blasted_at');
        });
    }
};

