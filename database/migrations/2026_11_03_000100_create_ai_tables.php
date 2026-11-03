<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->string('job', 64);
            $table->string('model', 64);
            $table->foreignId('ward_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('cache_creation_tokens')->default(0);
            $table->unsignedInteger('cache_read_tokens')->default(0);
            $table->boolean('is_batch')->default(false);
            $table->decimal('cost_usd', 12, 6)->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['created_at']);
            $table->index(['ward_id', 'created_at']);
        });

        Schema::create('ai_batches', function (Blueprint $table) {
            $table->id();
            $table->string('provider_batch_id')->unique();
            $table->string('kind', 32);
            // submitted, ended, processed, failed
            $table->string('status', 16)->default('submitted');
            $table->date('week_start')->nullable();
            $table->jsonb('request_map');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('weekly_briefs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ward_id')->constrained()->restrictOnDelete();
            $table->date('week_start');
            $table->text('body');
            $table->jsonb('rising_topics')->default('[]');
            $table->jsonb('improving_topics')->default('[]');
            $table->jsonb('stats')->nullable();
            // ai or template
            $table->string('source', 16);
            $table->string('model', 64)->nullable();
            $table->foreignId('ai_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['ward_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_briefs');
        Schema::dropIfExists('ai_batches');
        Schema::dropIfExists('ai_usage_logs');
    }
};
