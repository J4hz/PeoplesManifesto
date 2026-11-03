<?php

use App\Models\AuditLog;
use App\Models\User;

beforeEach(fn () => pilot());

it('sends a leader without an authenticator app to set one up', function () {
    $user = User::factory()->leaderFor(leaderFor())->create();

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('staff.security'));
    $this->actingAs($user)->get('/admin')->assertRedirect(route('staff.security'));
    $this->actingAs($user)->get(route('staff.security'))->assertOk()->assertSee('Set up authenticator app');
});

it('asks for the authenticator code after the password', function () {
    $user = leaderUser();

    $this->post('/staff/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('two-factor.login'));

    $this->assertGuest('web');
});

it('refuses suspended accounts at sign in', function () {
    $user = User::factory()->admin()->withTwoFactor()->create(['is_active' => false]);

    $this->post('/staff/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
});

it('lets a user turn on two factor and records it in the audit log', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post('/staff/user/two-factor-authentication')
        ->assertRedirect();

    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull();

    $engine = app(\PragmaRX\Google2FA\Google2FA::class);
    $code = $engine->getCurrentOtp(decrypt($user->two_factor_secret));

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post('/staff/user/confirmed-two-factor-authentication', ['code' => $code])
        ->assertRedirect();

    expect($user->fresh()->hasConfirmedTwoFactor())->toBeTrue()
        ->and(AuditLog::where('action', 'account.two_factor_confirmed')->where('actor_id', $user->id)->exists())->toBeTrue();
});

it('does not offer citizens the staff area', function () {
    $this->actingAs(citizen(), 'citizen')->get('/dashboard')->assertRedirect(route('login'));
});
