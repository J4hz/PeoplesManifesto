<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizens', function (Blueprint $table) {
            $table->id();
            // Ciphertext from the encrypted cast. Lookups use phone_hash.
            $table->text('phone');
            $table->string('phone_hash', 64)->unique();
            $table->foreignId('ward_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('locale', 5)->default('en');
            $table->decimal('trust_score', 5, 2)->default(1);
            $table->boolean('sms_notifications')->default(true);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone_hash', 64)->index();
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
        Schema::dropIfExists('citizens');
    }
};
