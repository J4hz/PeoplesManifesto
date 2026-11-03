<?php

use App\Models\AuditLog;
use App\Models\ClusterUpdate;
use App\Models\Rating;
use App\Notifications\IssueMarkedDone;
use App\Notifications\IssueUpdated;
use App\Services\ClusterRanker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => pilot());

it('shows a leader the ranked issues in their jurisdiction only', function () {
    $kiuu = clusterWithReporters([citizen('kiuu'), citizen('kiuu')], ['title' => 'Kiuu water problem']);
    $mwiki = clusterWithReporters([citizen('mwiki')], ['title' => 'Mwiki road problem']);
    app(ClusterRanker::class)->recalculate($kiuu);
    app(ClusterRanker::class)->recalculate($mwiki);

    $this->actingAs(leaderUser(leaderFor('mca', 'kiuu')))->get('/dashboard')
        ->assertOk()
        ->assertSee('Kiuu water problem')
        ->assertDontSee('Mwiki road problem')
        ->assertSee('Respond');

    $this->actingAs(leaderUser(leaderFor('mp', 'kiuu')))->get('/dashboard')
        ->assertSee('Kiuu water problem')
        ->assertSee('Mwiki road problem');
});

it('shows anonymous samples with translations and no phone numbers', function () {
    $citizen = citizen();
    $cluster = clusterWithReporters([$citizen]);
    app(ClusterRanker::class)->recalculate($cluster);

    $this->actingAs(leaderUser())->get("/dashboard/issues/{$cluster->id}")
        ->assertOk()
        ->assertSee('No water for a week now')
        ->assertSee('Hakuna maji kwa wiki moja sasa')
        ->assertDontSee($citizen->phone);
});

it('leaves out samples flagged for personal details', function () {
    $cluster = clusterWithReporters([citizen()]);
    DB::table('submissions')->update(['contains_personal_info' => true, 'translation' => 'Call Wanjiku on [phone]']);

    $this->actingAs(leaderUser())->get("/dashboard/issues/{$cluster->id}")
        ->assertOk()
        ->assertDontSee('Wanjiku');
});

it('blocks a leader from responding outside their jurisdiction', function () {
    $mwiki = clusterWithReporters([citizen('mwiki')]);
    $kiuuMca = leaderUser(leaderFor('mca', 'kiuu'));

    $this->actingAs($kiuuMca)->get("/dashboard/issues/{$mwiki->id}")->assertForbidden();
    $this->actingAs($kiuuMca)->post("/dashboard/issues/{$mwiki->id}/updates", ['message' => 'We are on it this week'])->assertForbidden();
});

it('publishes a leader update, notifies reporters and writes the audit log', function () {
    Notification::fake();
    $reporters = [citizen(), citizen()];
    $cluster = clusterWithReporters($reporters);
    $leader = leaderUser();

    $this->actingAs($leader)->post("/dashboard/issues/{$cluster->id}/updates", [
        'status' => 'in_progress',
        'message' => 'The crew starts on the pipe on Thursday morning.',
        'expected_completion_on' => now()->addWeek()->toDateString(),
        'notify_reporters' => '1',
    ])->assertRedirect();

    expect($cluster->fresh()->status)->toBe('in_progress')
        ->and($cluster->fresh()->first_response_at)->not->toBeNull()
        ->and(ClusterUpdate::sole()->isPublished())->toBeTrue();

    Notification::assertSentTo($reporters, IssueUpdated::class);

    $log = AuditLog::where('action', 'update.published')->sole();
    expect($log->actor_id)->toBe($leader->id)
        ->and($log->account_leader_id)->toBe($leader->leader_id)
        ->and($log->before['status'])->toBe('open')
        ->and($log->after['status'])->toBe('in_progress');

    $this->actingAs($leader)->get('/dashboard/audit')->assertOk()->assertSee('Published an update');
});

it('asks every reporter to rate when a leader marks the issue done', function () {
    Notification::fake();
    $reporters = [citizen(), citizen(), citizen()];
    $reporters[2]->forceFill(['sms_notifications' => false])->save();
    $cluster = clusterWithReporters($reporters);
    $leader = leaderUser();

    $this->actingAs($leader)->post("/dashboard/issues/{$cluster->id}/updates", [
        'status' => 'done',
        'message' => 'The pipe is fixed and water is back since Friday.',
    ])->assertRedirect();

    $cluster->refresh();
    expect($cluster->status)->toBe('done')
        ->and($cluster->done_round)->toBe(1)
        ->and($cluster->done_by_leader_id)->toBe($leader->leader_id);

    Notification::assertSentTo($reporters, IssueMarkedDone::class, fn ($n, $channels) => in_array('sms', $channels, true));
});

it('saves staff updates as drafts and refuses Done from staff', function () {
    Notification::fake();
    $leader = leaderFor();
    $staff = staffUser($leader);
    $cluster = clusterWithReporters([citizen()]);

    $this->actingAs($staff)->post("/dashboard/issues/{$cluster->id}/updates", [
        'status' => 'done',
        'message' => 'Staff trying to close the issue.',
    ])->assertSessionHasErrors('status');

    $this->actingAs($staff)->post("/dashboard/issues/{$cluster->id}/updates", [
        'status' => 'acknowledged',
        'message' => 'We have seen this and the MCA will visit on Monday.',
        'notify_reporters' => '1',
    ])->assertRedirect();

    $draft = ClusterUpdate::sole();
    expect($draft->isPublished())->toBeFalse()
        ->and($cluster->fresh()->status)->toBe('open');
    Notification::assertNothingSent();

    $this->actingAs($staff)->post("/dashboard/updates/{$draft->id}/publish")->assertForbidden();

    $this->actingAs(leaderUser($leader))->post("/dashboard/updates/{$draft->id}/publish")->assertRedirect();
    expect($draft->fresh()->isPublished())->toBeTrue()
        ->and($cluster->fresh()->status)->toBe('acknowledged')
        ->and(AuditLog::where('action', 'update.drafted')->exists())->toBeTrue()
        ->and(AuditLog::where('action', 'update.published')->exists())->toBeTrue();
});

it('shows the leader their rating record', function () {
    $leader = leaderFor();
    $reporter = citizen();
    $cluster = clusterWithReporters([$reporter], ['status' => 'done', 'done_round' => 1, 'done_at' => now(), 'done_by_leader_id' => $leader->id]);
    Rating::create(['cluster_id' => $cluster->id, 'round' => 1, 'leader_id' => $leader->id, 'citizen_ref' => $reporter->ref(), 'verdict' => 'fixed', 'stars' => 4, 'praise_tags' => ['fast_response'], 'thanks' => 'Asante sana for the quick work', 'channel' => 'app']);

    $this->actingAs(leaderUser($leader))->get('/dashboard/ratings')
        ->assertOk()
        ->assertSee('4.0')
        ->assertSee('100%')
        ->assertSee('Asante sana for the quick work');

    $this->get("/leaders/{$leader->slug}")->assertOk()->assertSee('Asante sana for the quick work');
});

it('keeps citizens and admins out of the leader dashboard', function () {
    $this->actingAs(adminUser())->get('/dashboard')->assertForbidden();
    $this->actingAs(leaderUser())->get('/admin')->assertForbidden();
});
