<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cluster_id')->constrained('issue_clusters')->restrictOnDelete();
            // Which Done round this rating belongs to. A reopened and re-fixed issue gets a fresh vote.
            $table->unsignedSmallInteger('round');
            $table->foreignId('leader_id')->nullable()->constrained()->nullOnDelete();
            $table->string('citizen_ref', 64);
            // fixed, partly, not_fixed
            $table->string('verdict', 16);
            // Empty for SMS ratings, which only ask for the verdict.
            $table->unsignedTinyInteger('stars')->nullable();
            $table->jsonb('praise_tags')->default('[]');
            $table->text('thanks')->nullable();
            $table->string('channel', 8);
            $table->timestamps();

            $table->unique(['cluster_id', 'round', 'citizen_ref']);
            $table->index(['leader_id', 'created_at']);
        });

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION ratings_immutable() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'ratings cannot be changed or removed, % is not allowed', TG_OP;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER ratings_block_changes
                BEFORE UPDATE OR DELETE ON ratings
                FOR EACH ROW EXECUTE FUNCTION ratings_immutable();

            CREATE TRIGGER ratings_block_truncate
                BEFORE TRUNCATE ON ratings
                FOR EACH STATEMENT EXECUTE FUNCTION ratings_immutable();
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
        DB::unprepared('DROP FUNCTION IF EXISTS ratings_immutable()');
    }
};
