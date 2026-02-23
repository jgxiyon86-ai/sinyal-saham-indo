<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signal_wa_blast_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('signal_wa_blast_batches')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tier_id')->nullable()->constrained('tiers')->nullOnDelete();
            $table->foreignId('signal_id')->nullable()->constrained('signals')->nullOnDelete();
            $table->string('client_name', 120)->nullable();
            $table->string('signal_title', 200)->nullable();
            $table->string('whatsapp_number', 30);
            $table->text('message');
            $table->string('image_url', 500)->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'status']);
            $table->index(['tier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signal_wa_blast_targets');
    }
};

