<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signal_wa_blast_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tier_id')->nullable()->constrained('tiers')->nullOnDelete();
            $table->json('signal_ids');
            $table->unsignedInteger('delay_seconds')->default(12);
            $table->unsignedInteger('max_recipients')->default(40);
            $table->string('opening_text', 300)->nullable();
            $table->string('closing_text', 300)->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('status', 32)->default('queued');
            $table->unsignedInteger('total_targets')->default(0);
            $table->unsignedInteger('pending_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signal_wa_blast_batches');
    }
};

