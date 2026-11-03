<?php

use App\Models\AuditLog;
use App\Models\ClusterUpdate;
use App\Models\IssueCluster;
use App\Models\Rating;
use Illuminate\Support\Facades\Gate;

beforeEach(fn () => pilot());

it('never lets anyone edit, hide or delete a submission', function () {
    $citizen = citizen();
    $cluster = clusterWithReporters([$citizen]);
    $submission = $cluster->submissions()->first();

    foreach ([$citizen, leaderUser(), staffUser(), adminUser()] as $actor) {
        foreach (['update', 'delete', 'hide'] as $ability) {
            expect(Gate::forUser($actor)->allows($ability, $submission))->toBeFalse();
        }
    }
});

it('never lets anyone change or delete a rating', function () {
    $rating = new Rating(['verdict' => 'fixed']);

    foreach ([citizen(), leaderUser(), staffUser(), adminUser()] as $actor) {
        foreach (['update', 'delete', 'hide'] as $ability) {
            expect(Gate::forUser($actor)->allows($ability, $rating))->toBeFalse();
        }
    }
});

it('keeps leaders inside their own jurisdiction', function () {
    $kiuuCluster = clusterWithReporters([citizen('kiuu')]);
    $mwikiCluster = clusterWithReporters([citizen('mwiki')]);

    $kiuuMca = leaderUser(leaderFor('mca', 'kiuu'));
    $ruiruMp = leaderUser(leaderFor('mp', 'kiuu'));

    expect(Gate::forUser($kiuuMca)->allows('respond', $kiuuCluster))->toBeTrue()
        ->and(Gate::forUser($kiuuMca)->allows('respond', $mwikiCluster))->toBeFalse()
        ->and(Gate::forUser($ruiruMp)->allows('respond', $kiuuCluster))->toBeTrue()
        ->and(Gate::forUser($ruiruMp)->allows('respond', $mwikiCluster))->toBeTrue();
});

it('lets staff draft but not mark done or publish', function () {
    $leader = leaderFor();
    $staff = staffUser($leader);
    $cluster = clusterWithReporters([citizen()]);
    $draft = ClusterUpdate::create(['cluster_id' => $cluster->id, 'leader_id' => $leader->id, 'author_user_id' => $staff->id, 'message' => 'Draft from staff']);

    expect(Gate::forUser($staff)->allows('respond', $cluster))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('markDone', $cluster))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('publish', $draft))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('delete', $draft))->toBeTrue();

    $leaderUser = leaderUser($leader);
    expect(Gate::forUser($leaderUser)->allows('markDone', $cluster))->toBeTrue()
        ->and(Gate::forUser($leaderUser)->allows('publish', $draft))->toBeTrue();
});

it('does not give admins leader powers or suspended users anything', function () {
    $cluster = clusterWithReporters([citizen()]);
    $admin = adminUser();

    expect(Gate::forUser($admin)->allows('respond', $cluster))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('leader-dashboard'))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('admin'))->toBeTrue();

    $suspended = leaderUser();
    $suspended->forceFill(['is_active' => false])->save();
    expect(Gate::forUser($suspended)->allows('respond', $cluster))->toBeFalse()
        ->and(Gate::forUser($suspended)->allows('leader-dashboard'))->toBeFalse();
});

it('lets only residents counted on a done issue rate it, once per round', function () {
    $reporter = citizen();
    $neighbour = citizen();
    $cluster = clusterWithReporters([$reporter], ['status' => IssueCluster::STATUS_DONE, 'done_round' => 1, 'done_at' => now()]);

    expect(Gate::forUser($reporter)->allows('rate', $cluster))->toBeTrue()
        ->and(Gate::forUser($neighbour)->allows('rate', $cluster))->toBeFalse()
        ->and(Gate::forUser(leaderUser())->allows('rate', $cluster))->toBeFalse();

    Rating::create(['cluster_id' => $cluster->id, 'round' => 1, 'citizen_ref' => $reporter->ref(), 'verdict' => 'fixed', 'channel' => 'app']);
    expect(Gate::forUser($reporter)->allows('rate', $cluster))->toBeFalse();

    $cluster->forceFill(['done_round' => 2])->save();
    expect(Gate::forUser($reporter)->allows('rate', $cluster->fresh()))->toBeTrue();
});

it('lets residents add their voice only in their own ward and only while open', function () {
    $cluster = clusterWithReporters([citizen('kiuu')]);

    expect(Gate::forUser(citizen('kiuu'))->allows('addVoice', $cluster))->toBeTrue()
        ->and(Gate::forUser(citizen('mwiki'))->allows('addVoice', $cluster))->toBeFalse();

    $cluster->forceFill(['status' => 'done'])->save();
    expect(Gate::forUser(citizen('kiuu'))->allows('addVoice', $cluster))->toBeFalse();
});

it('shows leaders their own account audit entries and admins all of them', function () {
    $leader = leaderFor();
    $own = AuditLog::create(['actor_type' => 'user', 'account_leader_id' => $leader->id, 'action' => 'x']);
    $other = AuditLog::create(['actor_type' => 'user', 'account_leader_id' => $leader->id + 999, 'action' => 'y']);

    $user = leaderUser($leader);
    expect(Gate::forUser($user)->allows('view', $own))->toBeTrue()
        ->and(Gate::forUser($user)->allows('view', $other))->toBeFalse()
        ->and(Gate::forUser(adminUser())->allows('view', $other))->toBeTrue()
        ->and(Gate::forUser($user)->allows('delete', $own))->toBeFalse()
        ->and(Gate::forUser(adminUser())->allows('update', $own))->toBeFalse();
});

it('lets only the author see their submission', function () {
    $author = citizen();
    $cluster = clusterWithReporters([$author]);
    $submission = $cluster->submissions()->first();

    expect(Gate::forUser($author)->allows('view', $submission))->toBeTrue()
        ->and(Gate::forUser(citizen())->allows('view', $submission))->toBeFalse()
        ->and(Gate::forUser(leaderUser())->allows('view', $submission))->toBeFalse();
});
