<?php

use App\Jobs\SendSms;
use App\Models\Citizen;
use App\Services\IdentityHasher;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    pilot();
    Bus::fake([SendSms::class]);
});

function codeFromSms(): string
{
    $code = null;
    Bus::assertDispatched(SendSms::class, function (SendSms $job) use (&$code) {
        preg_match('/\b(\d{6})\b/', $job->message, $m);
        $code = $m[1] ?? null;

        return true;
    });

    return (string) $code;
}

it('signs a resident in with a phone number and an SMS code', function () {
    $this->post('/sign-in', ['phone' => '0712 345 678'])->assertRedirect(route('citizen.sign-in.verify'));

    $this->post('/sign-in/code', ['code' => codeFromSms()])->assertRedirect(route('citizen.ward.choose'));

    $citizen = Citizen::sole();
    expect($citizen->phone)->toBe('+254712345678');
    $this->assertAuthenticatedAs($citizen, 'citizen');
});

it('stores the phone number encrypted and looks it up by keyed hash', function () {
    $this->post('/sign-in', ['phone' => '0712345678']);
    $this->post('/sign-in/code', ['code' => codeFromSms()]);

    $raw = DB::table('citizens')->first();
    expect($raw->phone)->not->toContain('712345678')
        ->and($raw->phone_hash)->toBe(app(IdentityHasher::class)->phone('+254712345678'));
});

it('rejects a wrong code and locks the code after too many attempts', function () {
    $this->post('/sign-in', ['phone' => '0712345678']);
    $code = codeFromSms();
    $wrong = $code === '000000' ? '111111' : '000000';

    foreach (range(1, config('civic.otp.max_attempts')) as $i) {
        $this->post('/sign-in/code', ['code' => $wrong])->assertSessionHasErrors('code');
    }

    $this->post('/sign-in/code', ['code' => $code])->assertSessionHasErrors('code');
    $this->assertGuest('citizen');
});

it('rejects numbers that are not Kenyan mobiles', function () {
    $this->post('/sign-in', ['phone' => '0812345678'])->assertSessionHasErrors('phone');
    Bus::assertNotDispatched(SendSms::class);
});

it('limits code requests per phone number', function () {
    foreach (range(1, 3) as $i) {
        $this->post('/sign-in', ['phone' => '0712345678'])->assertRedirect();
    }

    $this->post('/sign-in', ['phone' => '0712345678'])->assertStatus(429);
    $this->post('/sign-in', ['phone' => '0722000111'])->assertRedirect();
});

it('limits code requests per IP address across numbers', function () {
    foreach (range(1, 10) as $i) {
        $this->post('/sign-in', ['phone' => '07220001'.str_pad((string) $i, 2, '0', STR_PAD_LEFT)])->assertRedirect();
    }

    $this->post('/sign-in', ['phone' => '0733000999'])->assertStatus(429);
});

it('asks a new resident for their ward and then shows the raise form', function () {
    $citizen = Citizen::factory()->create(['ward_id' => null]);

    $this->actingAs($citizen, 'citizen')->get('/')->assertRedirect(route('citizen.ward.choose'));

    $this->actingAs($citizen, 'citizen')
        ->post('/welcome/ward', ['ward_id' => ward('mwiki')->id])
        ->assertRedirect(route('home'));

    expect($citizen->fresh()->ward_id)->toBe(ward('mwiki')->id);

    $this->actingAs($citizen->fresh(), 'citizen')->get('/')
        ->assertOk()
        ->assertSee('Raise an issue')
        ->assertSee('Mwiki');
});

it('shows guests the task first, not a marketing page', function () {
    $this->get('/')->assertOk()
        ->assertSee('Raise an issue in your ward')
        ->assertSee('Phone number')
        ->assertSee('Kiuu');
});
