<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cluster_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cluster_id')->constrained('issue_clusters')->restrictOnDelete();
            $table->foreignId('leader_id')->constrained()->restrictOnDelete();
            $table->foreignId('author_user_id')->constrained('users')->restrictOnDelete();
            // The status the cluster moved to with this update, if any.
            $table->string('status', 16)->nullable();
            $table->text('message');
            $table->date('expected_completion_on')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('notify_reporters')->default(true);
            // Null while it is a staff draft.
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['cluster_id', 'published_at']);
        });

        // A published update is part of the public record. Drafts stay editable.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION cluster_updates_freeze_published() RETURNS trigger AS $$
            BEGIN
                IF TG_OP = 'TRUNCATE' THEN
                    RAISE EXCEPTION 'cluster_updates cannot be truncated';
                END IF;
                IF OLD.published_at IS NOT NULL THEN
                    RAISE EXCEPTION 'published updates cannot be changed or removed';
                END IF;
                IF TG_OP = 'DELETE' THEN
                    RETURN OLD;
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER cluster_updates_freeze_published
                BEFORE UPDATE OR DELETE ON cluster_updates
                FOR EACH ROW EXECUTE FUNCTION cluster_updates_freeze_published();

            CREATE TRIGGER cluster_updates_block_truncate
                BEFORE TRUNCATE ON cluster_updates
                FOR EACH STATEMENT EXECUTE FUNCTION cluster_updates_freeze_published();
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('cluster_updates');
        DB::unprepared('DROP FUNCTION IF EXISTS cluster_updates_freeze_published()');
    }
};
