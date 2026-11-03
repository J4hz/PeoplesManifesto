<?php

use App\Models\IssueCluster;
use App\Models\Submission;
use App\Models\SubmissionAuthor;
use App\Services\ClusterRanker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => pilot());

it('takes a report, keeps identity apart, and groups it into a ranked issue', function () {
    $citizen = citizen();

    $this->actingAs($citizen, 'citizen')
        ->post('/reports', ['body' => 'Hakuna maji Kiuu kwa wiki moja sasa', 'category' => 'water'])
        ->assertRedirect(route('citizen.reports'));

    $submission = Submission::sole();
    expect($submission->processing_status)->toBe('processed')
        ->and($submission->category)->toBe('water')
        ->and($submission->cluster)->toBeInstanceOf(IssueCluster::class)
        ->and($submission->cluster->resident_count)->toBe(1);

    expect(Schema::getColumnListing('submissions'))->not->toContain('citizen_id');

    $raw = DB::table('submission_authors')->first();
    expect($raw->citizen_id)->not->toBe((string) $citizen->id)
        ->and($raw->citizen_ref)->toBe($citizen->ref())
        ->and(SubmissionAuthor::first()->citizen_id)->toBe((string) $citizen->id);

    $this->actingAs($citizen, 'citizen')->get('/my-reports')->assertOk()->assertSee('Hakuna maji Kiuu');
});

it('groups a second similar report from a neighbour into the same issue', function () {
    $this->actingAs(citizen(), 'citizen')->post('/reports', ['body' => 'No water in Kiuu taps for a week now', 'category' => 'water']);
    $this->actingAs(citizen(), 'citizen')->post('/reports', ['body' => 'Taps have no water in Kiuu this week', 'category' => 'water']);
    $this->actingAs(citizen('mwiki'), 'citizen')->post('/reports', ['body' => 'No water in Mwiki taps for a week now', 'category' => 'water']);

    expect(IssueCluster::where('ward_id', ward('kiuu')->id)->count())->toBe(1)
        ->and(IssueCluster::where('ward_id', ward('kiuu')->id)->first()->resident_count)->toBe(2)
        ->and(IssueCluster::where('ward_id', ward('mwiki')->id)->count())->toBe(1);
});

it('strips location data from photos and keeps them off the public disk', function () {
    Storage::fake('local');

    $image = imagecreatetruecolor(40, 30);
    ob_start();
    imagejpeg($image);
    $jpeg = ob_get_clean();
    // Add an EXIF block after the start-of-image marker, as a phone camera would.
    $exif = "Exif\0\0MM\0*\0\0\0\x08\0\0\0\0\0\0GPSLatitude-1.1500";
    $withExif = substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2);
    $path = tempnam(sys_get_temp_dir(), 'img').'.jpg';
    file_put_contents($path, $withExif);

    $this->actingAs(citizen(), 'citizen')->post('/reports', [
        'body' => 'Burst pipe near the stage flooding the road',
        'photo' => new UploadedFile($path, 'pipe.jpg', 'image/jpeg', null, true),
    ])->assertRedirect();

    $stored = Submission::sole()->photo_path;
    expect($stored)->toStartWith('submissions/');
    $contents = Storage::disk('local')->get($stored);
    expect($contents)->not->toContain('Exif')->not->toContain('GPSLatitude');
});

it('refuses files that are not images', function () {
    $this->actingAs(citizen(), 'citizen')->post('/reports', [
        'body' => 'Burst pipe near the stage flooding the road',
        'photo' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
    ])->assertSessionHasErrors('photo');

    expect(Submission::count())->toBe(0);
});

it('limits reports per resident', function () {
    $citizen = citizen();

    foreach (range(1, 6) as $i) {
        $this->actingAs($citizen, 'citizen')->post('/reports', ['body' => "Report number {$i} about the road"])->assertRedirect();
    }

    $this->actingAs($citizen, 'citizen')->post('/reports', ['body' => 'One report too many today'])->assertStatus(429);
});

it('counts a resident once when they add their voice', function () {
    $cluster = clusterWithReporters([citizen()]);
    app(ClusterRanker::class)->recalculate($cluster);
    $neighbour = citizen();

    $this->actingAs($neighbour, 'citizen')->post("/issues/{$cluster->id}/voice")->assertRedirect();
    $this->actingAs($neighbour, 'citizen')->post("/issues/{$cluster->id}/voice")->assertRedirect();

    expect($cluster->fresh()->resident_count)->toBe(2)
        ->and($cluster->fresh()->submission_count)->toBe(2);
});

it('stops residents adding their voice in another ward', function () {
    $cluster = clusterWithReporters([citizen('kiuu')]);

    $this->actingAs(citizen('mwiki'), 'citizen')->post("/issues/{$cluster->id}/voice")->assertForbidden();
});

it('shows the public ward page without anything that identifies a resident', function () {
    $citizen = citizen();
    $this->actingAs($citizen, 'citizen')->post('/reports', ['body' => 'Hakuna maji Kiuu kwa wiki moja sasa', 'category' => 'water']);
    auth('citizen')->logout();

    $this->get('/wards/kiuu')->assertOk()
        ->assertSee('Hakuna maji Kiuu')
        ->assertDontSee($citizen->phone)
        ->assertDontSee(substr($citizen->phone, 4));
});

it('serves a report photo only to its author and the leaders of that ward', function () {
    Storage::fake('local');
    $author = citizen();
    $this->actingAs($author, 'citizen')->post('/reports', [
        'body' => 'Burst pipe near the stage flooding the road',
        'photo' => UploadedFile::fake()->image('pipe.jpg', 50, 40),
    ]);
    $submission = Submission::sole();

    $this->actingAs($author, 'citizen')->get("/my-reports/{$submission->id}/photo")->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    $this->actingAs(citizen(), 'citizen')->get("/my-reports/{$submission->id}/photo")->assertForbidden();
    $this->actingAs(leaderUser(leaderFor('mca', 'kiuu')), 'web')->get("/dashboard/submissions/{$submission->id}/photo")->assertOk();
    $this->actingAs(leaderUser(leaderFor('mca', 'mwiki')), 'web')->get("/dashboard/submissions/{$submission->id}/photo")->assertForbidden();
});
