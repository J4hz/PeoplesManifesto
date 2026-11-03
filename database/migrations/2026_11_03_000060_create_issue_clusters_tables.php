<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_clusters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ward_id')->constrained()->restrictOnDelete();
            $table->foreignId('routed_office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->string('category', 24);
            $table->string('title');
            $table->text('summary')->nullable();
            // open, acknowledged, in_progress, done, reopened
            $table->string('status', 16)->default('open');
            $table->string('severity', 16)->default('annoying');
            $table->unsignedInteger('resident_count')->default(0);
            $table->unsignedInteger('submission_count')->default(0);
            $table->unsignedInteger('areas_count')->default(0);
            // new, rising, steady, falling
            $table->string('trend', 8)->default('new');
            $table->float('growth')->default(0);
            $table->float('score')->default(0);
            $table->vector('centroid', 1024)->nullable();
            $table->unsignedInteger('centroid_weight')->default(0);
            $table->timestamp('first_reported_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->foreignId('done_by_leader_id')->nullable()->constrained('leaders')->nullOnDelete();
            $table->unsignedSmallInteger('done_round')->default(0);
            $table->timestamp('rating_window_closed_at')->nullable();
            $table->timestamp('reopened_at')->nullable();
            $table->unsignedInteger('summary_member_count')->default(0);
            $table->timestamps();

            $table->index(['ward_id', 'status', 'score']);
            $table->index(['ward_id', 'category', 'status']);
        });

        Schema::create('cluster_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cluster_id')->constrained('issue_clusters')->restrictOnDelete();
            $table->foreignId('submission_id')->unique()->constrained()->restrictOnDelete();
            $table->float('similarity');
            $table->timestamps();
        });

        DB::statement('CREATE INDEX issue_clusters_centroid_hnsw ON issue_clusters USING hnsw (centroid vector_cosine_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('cluster_memberships');
        Schema::dropIfExists('issue_clusters');
    }
};
