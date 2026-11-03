<?php

use App\Models\Citizen;
use App\Models\IssueCluster;
use App\Models\Leader;
use App\Models\Office;
use App\Models\Submission;
use App\Models\SubmissionAuthor;
use App\Models\User;
use App\Models\Ward;
use Database\Seeders\PilotAreaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

function pilot(): void
{
    (new PilotAreaSeeder)->run();
}

function ward(string $slug = 'kiuu'): Ward
{
    if (! Ward::where('slug', $slug)->exists()) {
        pilot();
    }

    return Ward::where('slug', $slug)->firstOrFail();
}

function leaderFor(string $type = 'mca', string $wardSlug = 'kiuu'): Leader
{
    $office = ward($wardSlug)->officeOfType($type);

    return Leader::create([
        'office_id' => $office->id,
        'name' => 'Test '.strtoupper($type).' '.$wardSlug,
        'slug' => 'test-'.$type.'-'.$wardSlug.'-'.uniqid(),
    ]);
}

function leaderUser(?Leader $leader = null): User
{
    return User::factory()->leaderFor($leader ?? leaderFor())->withTwoFactor()->create();
}

function staffUser(?Leader $leader = null): User
{
    return User::factory()->staffFor($leader ?? leaderFor())->withTwoFactor()->create();
}

function adminUser(): User
{
    return User::factory()->admin()->withTwoFactor()->create();
}

function citizen(string $wardSlug = 'kiuu', int $daysOld = 30): Citizen
{
    return Citizen::factory()->inWard(ward($wardSlug))->createdDaysAgo($daysOld)->create();
}

/**
 * A cluster with one processed report per given citizen.
 *
 * @param  list<Citizen>  $reporters
 */
function clusterWithReporters(array $reporters, array $attributes = []): IssueCluster
{
    $cluster = IssueCluster::factory()->create(array_merge(['ward_id' => $reporters[0]->ward_id ?? ward()->id], $attributes));

    foreach ($reporters as $citizen) {
        $submission = Submission::factory()->processed($cluster->category)->create(['ward_id' => $cluster->ward_id]);
        SubmissionAuthor::create([
            'submission_id' => $submission->id,
            'citizen_ref' => $citizen->ref(),
            'citizen_id' => (string) $citizen->id,
        ]);
        $cluster->memberships()->create(['submission_id' => $submission->id, 'similarity' => 0.9]);
    }

    return $cluster->refresh();
}
