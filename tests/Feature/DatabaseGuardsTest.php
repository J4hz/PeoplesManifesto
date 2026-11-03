<?php

use App\Models\AuditLog;
use App\Models\ClusterUpdate;
use App\Models\Rating;
use App\Models\Submission;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

// Each blocked statement runs in its own savepoint, because Postgres aborts the
// surrounding test transaction after any error.

beforeEach(fn () => pilot());

it('blocks updates, deletes and truncation of the audit log in the database', function () {
    $log = app(AuditLogger::class)->record('test.action');

    expect(fn () => DB::transaction(fn () => DB::table('audit_logs')->where('id', $log->id)->update(['action' => 'changed'])))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => DB::table('audit_logs')->where('id', $log->id)->delete()))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => DB::statement('TRUNCATE audit_logs')))->toThrow(QueryException::class);

    expect(AuditLog::find($log->id)->action)->toBe('test.action');
});

it('blocks deleting a report or changing what the resident wrote', function () {
    $submission = Submission::factory()->create(['body' => 'Original words']);

    expect(fn () => DB::transaction(fn () => DB::table('submissions')->where('id', $submission->id)->update(['body' => 'Edited'])))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => DB::table('submissions')->where('id', $submission->id)->delete()))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => DB::table('submissions')->where('id', $submission->id)->update(['created_at' => now()->subYear()])))->toThrow(QueryException::class);

    DB::table('submissions')->where('id', $submission->id)->update(['translation' => 'Original words', 'processing_status' => 'processed']);
    expect($submission->fresh()->body)->toBe('Original words')
        ->and($submission->fresh()->processing_status)->toBe('processed');
});

it('blocks changing or deleting a rating', function () {
    $cluster = clusterWithReporters([citizen()], ['status' => 'done', 'done_round' => 1, 'done_at' => now()]);
    $rating = Rating::create(['cluster_id' => $cluster->id, 'round' => 1, 'citizen_ref' => 'ref', 'verdict' => 'not_fixed', 'channel' => 'app']);

    expect(fn () => DB::transaction(fn () => DB::table('ratings')->where('id', $rating->id)->update(['verdict' => 'fixed'])))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => DB::table('ratings')->where('id', $rating->id)->delete()))->toThrow(QueryException::class);
});

it('freezes published updates but lets drafts be discarded', function () {
    $leader = leaderFor();
    $user = leaderUser($leader);
    $cluster = clusterWithReporters([citizen()]);

    $draft = ClusterUpdate::create(['cluster_id' => $cluster->id, 'leader_id' => $leader->id, 'author_user_id' => $user->id, 'message' => 'Draft text here']);
    $published = ClusterUpdate::create(['cluster_id' => $cluster->id, 'leader_id' => $leader->id, 'author_user_id' => $user->id, 'message' => 'Published text', 'published_at' => now()]);

    expect(fn () => DB::transaction(fn () => DB::table('cluster_updates')->where('id', $published->id)->update(['message' => 'Rewritten'])))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => DB::table('cluster_updates')->where('id', $published->id)->delete()))->toThrow(QueryException::class);

    $draft->delete();
    expect(ClusterUpdate::find($draft->id))->toBeNull();
});
