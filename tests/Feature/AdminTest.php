<?php

use App\Jobs\ProcessSubmission;
use App\Models\AiUsageLog;
use App\Models\AuditLog;
use App\Models\Leader;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => pilot());

it('lets an admin invite a leader who must then set a password and an authenticator app', function () {
    Notification::fake();
    $admin = adminUser();
    $office = ward()->officeOfType('mca');

    $this->actingAs($admin)->post('/admin/people/leaders', [
        'office_id' => $office->id,
        'name' => 'Jane Wanjiru',
        'email' => 'jane@example.test',
    ])->assertRedirect();

    $user = User::where('email', 'jane@example.test')->sole();
    expect($user->isLeader())->toBeTrue()
        ->and($user->hasConfirmedTwoFactor())->toBeFalse()
        ->and(Leader::where('office_id', $office->id)->where('is_current', true)->sole()->user_id)->toBe($user->id)
        ->and(AuditLog::where('action', 'leader.created')->where('actor_id', $admin->id)->exists())->toBeTrue();

    Notification::assertSentTo($user, ResetPassword::class);
});

it('shows admins the failed intake queue without the report text and lets them retry', function () {
    Bus::fake();
    $citizen = citizen();
    $submission = Submission::create(['ward_id' => $citizen->ward_id, 'kind' => 'report', 'channel' => 'app', 'body' => 'Private words of a resident', 'processing_status' => 'failed', 'processing_error' => 'Intake output failed validation']);
    DB::table('submissions')->where('id', $submission->id)->update(['processing_status' => 'failed']);
    $this->travel(20)->minutes();

    $admin = adminUser();
    $this->actingAs($admin)->get('/admin/intake')->assertOk()
        ->assertSee('Intake output failed validation')
        ->assertDontSee('Private words of a resident');

    $this->actingAs($admin)->post("/admin/intake/{$submission->id}/retry")->assertRedirect();
    Bus::assertDispatched(ProcessSubmission::class);
    expect($submission->fresh()->processing_status)->toBe('pending');
});

it('lets an admin suspend a leader but not themselves', function () {
    $admin = adminUser();
    $leader = leaderUser();

    $this->actingAs($admin)->patch("/admin/people/{$leader->id}/active", ['is_active' => 0])->assertRedirect();
    expect($leader->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)->patch("/admin/people/{$admin->id}/active", ['is_active' => 0])->assertForbidden();
});

it('shows AI cost per ward per week', function () {
    AiUsageLog::create(['job' => 'intake', 'model' => 'claude-haiku-5-5', 'ward_id' => ward()->id, 'input_tokens' => 100, 'output_tokens' => 50, 'cost_usd' => 0.0123]);

    $this->actingAs(adminUser())->get('/admin/ai-usage')->assertOk()->assertSee('Kiuu')->assertSee('$0.0123');
});

it('lets an admin add an area to a ward', function () {
    $this->actingAs(adminUser())->post('/admin/wards/kiuu/areas', ['name' => 'Kiuu Stage'])->assertRedirect();

    expect(ward()->areas()->pluck('name')->all())->toBe(['Kiuu Stage']);
});

it('renders every admin page', function () {
    $admin = adminUser();
    foreach (['/admin', '/admin/wards', '/admin/people', '/admin/intake', '/admin/ai-usage', '/admin/audit'] as $url) {
        $this->actingAs($admin)->get($url)->assertOk();
    }
});

it('keeps Horizon behind the admin gate', function () {
    $this->actingAs(leaderUser())->get('/horizon')->assertForbidden();
    $this->actingAs(adminUser())->get('/horizon')->assertOk();
});
