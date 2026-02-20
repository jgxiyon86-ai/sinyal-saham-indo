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
        Schema::create('wa_blast_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('message_template_id')->nullable()->constrained('message_templates')->nullOnDelete();
            $table->enum('blast_type', ['birthday', 'holiday', 'general'])->default('general');
            $table->json('filters')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->longText('rendered_messages')->nullable();
            $table->string('status')->default('preview');
            $table->timestamp('blasted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_blast_logs');
    }
};
