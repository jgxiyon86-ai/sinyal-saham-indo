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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'client'])->default('client')->after('password');
            $table->foreignId('tier_id')->nullable()->after('role')->constrained('tiers')->nullOnDelete();
            $table->text('address')->nullable()->after('tier_id');
            $table->string('whatsapp_number')->nullable()->after('address');
            $table->date('birth_date')->nullable()->after('whatsapp_number');
            $table->string('religion', 30)->nullable()->after('birth_date');
            $table->decimal('capital_amount', 15, 2)->default(0)->after('religion');
            $table->boolean('is_active')->default(true)->after('capital_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tier_id');
            $table->dropColumn([
                'role',
                'address',
                'whatsapp_number',
                'birth_date',
                'religion',
                'capital_amount',
                'is_active',
            ]);
        });
    }
};
