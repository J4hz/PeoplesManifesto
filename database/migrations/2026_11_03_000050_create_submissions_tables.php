<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // No citizen column on purpose. The link lives in submission_authors.
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ward_id')->constrained()->restrictOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->restrictOnDelete();
            // report: the resident wrote something. voice: they pressed "+" on an existing issue.
            $table->string('kind', 8)->default('report');
            $table->string('channel', 8);
            $table->text('body')->nullable();
            $table->string('stated_category', 24)->nullable();
            $table->string('stated_severity', 16)->nullable();
            $table->string('photo_path')->nullable();

            $table->string('category', 24)->nullable();
            $table->string('severity', 16)->nullable();
            $table->string('original_language', 16)->nullable();
            $table->text('translation')->nullable();
            $table->string('ai_title')->nullable();
            $table->text('ai_summary')->nullable();
            $table->boolean('contains_personal_info')->default(false);
            $table->boolean('is_actionable')->default(true);

            // pending, processed, failed
            $table->string('processing_status', 16)->default('pending');
            $table->unsignedSmallInteger('processing_attempts')->default(0);
            $table->string('processing_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->vector('embedding', 1024)->nullable();
            $table->timestamps();

            $table->index(['ward_id', 'created_at']);
            $table->index('processing_status');
        });

        Schema::create('submission_authors', function (Blueprint $table) {
            $table->foreignId('submission_id')->primary()->constrained()->restrictOnDelete();
            // HMAC of the citizen id for lookups and distinct counts.
            $table->string('citizen_ref', 64)->index();
            // Encrypted citizen id, needed only to send notifications.
            // Emptied when the resident deletes their account, the HMAC stays so counts hold.
            $table->text('citizen_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Nobody, including admins, can delete a report or change what the resident wrote.
        // Processing columns stay writable so intake can fill them in.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION submissions_protect_original() RETURNS trigger AS $$
            BEGIN
                IF TG_OP IN ('DELETE', 'TRUNCATE') THEN
                    RAISE EXCEPTION 'submissions cannot be deleted';
                END IF;
                IF NEW.body IS DISTINCT FROM OLD.body
                    OR NEW.kind IS DISTINCT FROM OLD.kind
                    OR NEW.channel IS DISTINCT FROM OLD.channel
                    OR NEW.stated_category IS DISTINCT FROM OLD.stated_category
                    OR NEW.stated_severity IS DISTINCT FROM OLD.stated_severity
                    OR NEW.photo_path IS DISTINCT FROM OLD.photo_path
                    OR NEW.created_at IS DISTINCT FROM OLD.created_at THEN
                    RAISE EXCEPTION 'what a resident submitted cannot be changed';
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER submissions_protect_original
                BEFORE UPDATE OR DELETE ON submissions
                FOR EACH ROW EXECUTE FUNCTION submissions_protect_original();

            CREATE TRIGGER submissions_block_truncate
                BEFORE TRUNCATE ON submissions
                FOR EACH STATEMENT EXECUTE FUNCTION submissions_protect_original();
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_authors');
        Schema::dropIfExists('submissions');
        DB::unprepared('DROP FUNCTION IF EXISTS submissions_protect_original()');
    }
};
