<?php

use App\Jobs\SendSms;
use App\Models\Citizen;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;

beforeEach(fn () => pilot());

it('shows the sign-in code on screen and a banner on every page in demo mode', function () {
    config(['civic.demo.enabled' => true]);
    Bus::fake([SendSms::class]);

    $this->get('/')->assertSee('Demo site. Reports here do not reach any real leader.');

    $this->post('/sign-in', ['phone' => '0712345678'])->assertRedirect(route('citizen.sign-in.verify'));

    $page = $this->get('/sign-in/code')->assertOk()->assertSee('Demo site: your code is');
    preg_match('/your code is (\d{6})/', $page->getContent(), $m);

    $this->post('/sign-in/code', ['code' => $m[1]])->assertRedirect(route('citizen.ward.choose'));
    expect(Citizen::count())->toBe(1);
});

it('never shows the code or the banner outside demo mode', function () {
    Bus::fake([SendSms::class]);

    $this->get('/')->assertDontSee('Demo site');
    $this->post('/sign-in', ['phone' => '0712345678']);
    $this->get('/sign-in/code')->assertOk()->assertDontSee('your code is');
});

it('refuses to start in production with demo mode on', function () {
    config(['civic.demo.enabled' => true]);
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => (new AppServiceProvider(app()))->boot())->toThrow(RuntimeException::class);
});

it('seeds demo accounts with the configured staff password on a demo site', function () {
    config(['civic.demo.enabled' => true, 'civic.demo.staff_password' => 'a-demo-password-for-guests']);
    app()->detectEnvironment(fn () => 'demo');

    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'admin@manifesto.test')->sole();
    expect(Hash::check('a-demo-password-for-guests', $admin->password))->toBeTrue()
        ->and($admin->hasConfirmedTwoFactor())->toBeTrue();
});
