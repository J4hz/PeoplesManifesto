<?php

use App\Actions\AddVoice;
use App\Actions\CreateSubmission;
use App\Models\Citizen;
use App\Models\IssueCluster;
use App\Services\ReportFeed;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;

beforeEach(fn () => pilot());

function fileReport(string $ward, string $body): Citizen
{
    $citizen = citizen($ward);
    app(CreateSubmission::class)->handle($citizen, ['body' => $body], null, 'app');

    return $citizen;
}

it('shows a new complaint to the leaders of that ward as soon as it is filed', function () {
    $resident = fileReport('kiuu', 'The borehole pump at the market has been broken since Monday');
    fileReport('mwiki', 'Garbage has piled up behind the Mwiki shops for two weeks');

    $mca = leaderUser(leaderFor('mca', 'kiuu'));

    $this->actingAs($mca)->get('/dashboard/reports')->assertOk()
        ->assertSee('The borehole pump at the market has been broken since Monday')
        ->assertDontSee('Garbage has piled up behind the Mwiki shops')
        ->assertDontSee($resident->phone);

    $this->actingAs($mca)->get('/dashboard')->assertOk()
        ->assertSee('Latest reports')
        ->assertSee('The borehole pump at the market has been broken since Monday');

    $this->actingAs(leaderUser(leaderFor('mp', 'kiuu')))->get('/dashboard/reports')->assertOk()
        ->assertSee('The borehole pump at the market')
        ->assertSee('Garbage has piled up behind the Mwiki shops');
});

it('lets staff see complaints too', function () {
    fileReport('kiuu', 'Street lights at the stage have been off all week');

    $this->actingAs(staffUser(leaderFor('mca', 'kiuu')))->get('/dashboard/reports')->assertOk()
        ->assertSee('Street lights at the stage have been off all week');
});

it('shows admins complaints from every ward', function () {
    fileReport('kiuu', 'The borehole pump at the market has been broken since Monday');
    fileReport('mwiki', 'Garbage has piled up behind the Mwiki shops for two weeks');

    $admin = adminUser();
    $this->actingAs($admin)->get('/admin/reports')->assertOk()
        ->assertSee('The borehole pump at the market')
        ->assertSee('Garbage has piled up behind the Mwiki shops');
    $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Garbage has piled up behind the Mwiki shops');
});

it('hides the text of a complaint flagged for personal details', function () {
    fileReport('kiuu', 'Call Wanjiku on 0722000111 about the broken pipe');
    DB::table('submissions')->update(['contains_personal_info' => true]);

    $this->actingAs(leaderUser())->get('/dashboard/reports')->assertOk()
        ->assertDontSee('Wanjiku')
        ->assertDontSee('0722000111')
        ->assertSee('Text hidden because it mentions a person or a phone number');

    // The issue it started is public, so its title must not carry the name either.
    expect(IssueCluster::sole()->title)->toBe('Water issue in Kiuu');
    $this->get('/wards/kiuu')->assertOk()->assertDontSee('Wanjiku');
});

it('keeps residents out of the staff report lists', function () {
    $this->actingAs(citizen(), 'citizen')->get('/dashboard/reports')->assertRedirect(route('login'));
    $this->actingAs(citizen(), 'citizen')->get('/admin/reports')->assertRedirect(route('login'));
});

it('sends each role to its own home page after the authenticator code', function (string $role, string $home) {
    $user = match ($role) {
        'admin' => adminUser(),
        'leader' => leaderUser(),
    };

    $this->post('/staff/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('two-factor.login'));

    $code = app(Google2FA::class)->getCurrentOtp(decrypt($user->two_factor_secret));
    $this->post('/staff/two-factor-challenge', ['code' => $code])->assertRedirect($home);
})->with([
    'admin' => ['admin', '/admin'],
    'leader' => ['leader', '/dashboard'],
]);

it('lists written complaints only, not "+" taps', function () {
    $cluster = clusterWithReporters([citizen()]);
    app(AddVoice::class)->handle(citizen(), $cluster);

    $reports = app(ReportFeed::class)->latest(null, 10);

    expect($reports)->toHaveCount(1)
        ->and($reports->every(fn ($r) => $r->kind === 'report'))->toBeTrue();
});
