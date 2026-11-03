<?php

use App\Jobs\CheckDoneClusters;
use App\Models\AuditLog;
use App\Models\IssueCluster;
use App\Models\Rating;
use App\Notifications\IssueReopened;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    pilot();
    Notification::fake();
});

/**
 * @param  array<string, int>  $verdicts
 */
function doneClusterWithRatings(array $verdicts, int $daysSinceDone, ?\App\Models\Leader $leader = null): IssueCluster
{
    $leader ??= leaderFor();
    $cluster = IssueCluster::factory()->create([
        'ward_id' => ward()->id,
        'status' => 'done',
        'done_round' => 1,
        'done_at' => now()->subDays($daysSinceDone),
        'done_by_leader_id' => $leader->id,
    ]);

    $n = 0;
    foreach ($verdicts as $verdict => $count) {
        foreach (range(1, $count) as $i) {
            Rating::create(['cluster_id' => $cluster->id, 'round' => 1, 'leader_id' => $leader->id, 'citizen_ref' => 'ref-'.(++$n), 'verdict' => $verdict, 'channel' => 'app']);
        }
    }

    return $cluster;
}

it('reopens after seven days when more than half say not fixed, and tells the leader', function () {
    $leader = leaderFor();
    $user = leaderUser($leader);
    $cluster = doneClusterWithRatings(['not_fixed' => 3, 'fixed' => 1, 'partly' => 1], 8, $leader);

    (new CheckDoneClusters)->handle(app(\App\Services\AuditLogger::class));

    $cluster->refresh();
    expect($cluster->status)->toBe('reopened')
        ->and($cluster->reopened_at)->not->toBeNull()
        ->and($cluster->rating_window_closed_at)->not->toBeNull();

    Notification::assertSentTo($user, IssueReopened::class);
    expect(AuditLog::where('action', 'cluster.reopened_by_residents')->where('account_leader_id', $leader->id)->exists())->toBeTrue();
});

it('decides early once twenty residents have rated', function () {
    $cluster = doneClusterWithRatings(['not_fixed' => 11, 'fixed' => 9], 2);

    (new CheckDoneClusters)->handle(app(\App\Services\AuditLogger::class));

    expect($cluster->fresh()->status)->toBe('reopened');
});

it('waits while the window is open and fewer than twenty have rated', function () {
    $cluster = doneClusterWithRatings(['not_fixed' => 15], 3);

    (new CheckDoneClusters)->handle(app(\App\Services\AuditLogger::class));

    expect($cluster->fresh()->status)->toBe('done')
        ->and($cluster->fresh()->rating_window_closed_at)->toBeNull();
});

it('keeps the issue done when exactly half say not fixed', function () {
    $cluster = doneClusterWithRatings(['not_fixed' => 2, 'fixed' => 1, 'partly' => 1], 8);

    (new CheckDoneClusters)->handle(app(\App\Services\AuditLogger::class));

    expect($cluster->fresh()->status)->toBe('done')
        ->and($cluster->fresh()->rating_window_closed_at)->not->toBeNull();
    Notification::assertNothingSent();
});

it('closes the window with no change when nobody rated', function () {
    $cluster = doneClusterWithRatings([], 8);

    (new CheckDoneClusters)->handle(app(\App\Services\AuditLogger::class));

    expect($cluster->fresh()->status)->toBe('done')
        ->and($cluster->fresh()->rating_window_closed_at)->not->toBeNull();
});

it('only counts ratings from the current Done round', function () {
    $cluster = doneClusterWithRatings(['fixed' => 3], 8);
    foreach (range(1, 5) as $i) {
        Rating::create(['cluster_id' => $cluster->id, 'round' => 2, 'citizen_ref' => 'r2-'.$i, 'verdict' => 'not_fixed', 'channel' => 'app']);
    }

    (new CheckDoneClusters)->handle(app(\App\Services\AuditLogger::class));

    expect($cluster->fresh()->status)->toBe('done');
});

it('lets residents who reported rate a fix through the app, once', function () {
    $reporter = citizen();
    $cluster = clusterWithReporters([$reporter], ['status' => 'done', 'done_round' => 1, 'done_at' => now()]);

    $this->actingAs($reporter, 'citizen')->get("/issues/{$cluster->id}/rate")->assertOk()->assertSee('Was it fixed?');

    $this->actingAs($reporter, 'citizen')->post("/issues/{$cluster->id}/rate", [
        'verdict' => 'partly',
        'stars' => 3,
        'praise_tags' => ['kept_us_updated'],
        'thanks' => 'Thanks for the updates.',
    ])->assertRedirect(route('clusters.show', $cluster));

    $rating = Rating::sole();
    expect($rating->verdict)->toBe('partly')
        ->and($rating->citizen_ref)->toBe($reporter->ref())
        ->and($rating->praise_tags)->toBe(['kept_us_updated']);

    $this->actingAs($reporter, 'citizen')->post("/issues/{$cluster->id}/rate", ['verdict' => 'fixed', 'stars' => 5])->assertForbidden();
    $this->actingAs(citizen(), 'citizen')->get("/issues/{$cluster->id}/rate")->assertForbidden();
});

it('limits rating attempts per resident', function () {
    $reporter = citizen();
    $cluster = clusterWithReporters([$reporter], ['status' => 'done', 'done_round' => 1, 'done_at' => now()]);

    foreach (range(1, 20) as $i) {
        $this->actingAs($reporter, 'citizen')->post("/issues/{$cluster->id}/rate", ['verdict' => 'fixed', 'stars' => 5]);
    }

    $this->actingAs($reporter, 'citizen')->post("/issues/{$cluster->id}/rate", ['verdict' => 'fixed', 'stars' => 5])->assertStatus(429);
});

it('serves the live fixed count as JSON', function () {
    $cluster = doneClusterWithRatings(['fixed' => 2, 'not_fixed' => 1], 1);

    $this->getJson("/issues/{$cluster->id}/fixed-count")->assertOk()->assertExactJson(['fixed' => 2, 'total' => 3]);
});
