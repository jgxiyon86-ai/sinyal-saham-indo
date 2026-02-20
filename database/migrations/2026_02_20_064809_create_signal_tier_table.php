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
        Schema::create('signal_tier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tier_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['signal_id', 'tier_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signal_tier');
    }
};
