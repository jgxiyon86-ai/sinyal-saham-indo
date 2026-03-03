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
        Schema::table('signal_wa_blast_batches', function (Blueprint $table) {
            $table->boolean('group_messages')->default(false)->after('image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signal_wa_blast_batches', function (Blueprint $table) {
            $table->dropColumn('group_messages');
        });
    }
};
