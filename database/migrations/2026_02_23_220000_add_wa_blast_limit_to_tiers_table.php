<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiers', function (Blueprint $table) {
            $table->unsignedInteger('wa_blast_limit')
                ->default(60)
                ->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('tiers', function (Blueprint $table) {
            $table->dropColumn('wa_blast_limit');
        });
    }
};

