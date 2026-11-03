<?php

use App\Jobs\SendSms;
use App\Models\Citizen;
use App\Models\Rating;
use App\Models\Submission;
use App\Services\IdentityHasher;
use App\Sms\SmsGateway;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    pilot();
    Bus::fake([SendSms::class]);
});

function sms(string $text, string $from = '+254711222333', string $token = 'test-webhook-token-0123456789abcdef')
{
    return test()->post("/webhooks/africastalking/{$token}/sms", [
        'from' => $from,
        'to' => '12345',
        'text' => $text,
        'id' => 'ATXid_'.uniqid(),
        'date' => now()->toDateTimeString(),
    ]);
}

function lastReply(): string
{
    $reply = '';
    Bus::assertDispatched(SendSms::class, function (SendSms $job) use (&$reply) {
        $reply = $job->message;

        return true;
    });

    return $reply;
}

it('rejects callbacks without the right token', function () {
    sms('Hello', token: 'wrong-token-wrong-token-wrong')->assertForbidden();
    Bus::assertNotDispatched(SendSms::class);
});

it('rejects callbacks from addresses outside the allowlist when one is set', function () {
    config(['services.africastalking.webhook_ips' => ['196.201.214.0/24']]);

    sms('Hello')->assertForbidden();
});

it('takes a new resident from first text to a filed report', function () {
    sms('Hi')->assertOk();
    expect(lastReply())->toContain('Which ward')->toContain('6 Kiuu');

    sms('6')->assertOk();
    expect(lastReply())->toContain('What is the problem');

    sms('No water in our taps for a week near the stage')->assertOk();
    expect(lastReply())->toContain('Reply 1 Annoying, 2 Serious, 3 Urgent');

    sms('3')->assertOk();

    $submission = Submission::sole();
    expect($submission->channel)->toBe('sms')
        ->and($submission->stated_severity)->toBe('urgent')
        ->and($submission->ward->slug)->toBe('kiuu')
        ->and(lastReply())->toContain('Your report number is '.$submission->id);

    expect(Citizen::sole()->ward->slug)->toBe('kiuu');
});

it('keeps a first message that already describes the problem', function () {
    sms('Hakuna maji Kiuu kwa wiki nzima sasa');
    sms('6');
    expect(lastReply())->toContain('How serious');

    sms('2');
    expect(Submission::sole()->body)->toBe('Hakuna maji Kiuu kwa wiki nzima sasa');
});

it('switches the conversation to Kiswahili', function () {
    sms('SW');
    expect(lastReply())->toContain('Unaishi wadi gani');
});

it('asks again when the ward number is not on the list', function () {
    sms('Hi');
    sms('42');
    expect(lastReply())->toContain('did not get that');
});

it('records a verdict sent by SMS for the latest done issue', function () {
    $citizen = Citizen::factory()->inWard(ward())->create(['phone' => '+254711222333', 'phone_hash' => app(IdentityHasher::class)->phone('+254711222333')]);
    $cluster = clusterWithReporters([$citizen], ['status' => 'done', 'done_round' => 1, 'done_at' => now()]);

    sms('3')->assertOk();

    $rating = Rating::sole();
    expect($rating->verdict)->toBe('not_fixed')
        ->and($rating->channel)->toBe('sms')
        ->and($rating->stars)->toBeNull()
        ->and($rating->cluster_id)->toBe($cluster->id)
        ->and(lastReply())->toContain('Your answer is counted');
});

it('stops updates when a resident replies STOP', function () {
    $citizen = Citizen::factory()->inWard(ward())->create(['phone' => '+254711222333', 'phone_hash' => app(IdentityHasher::class)->phone('+254711222333')]);

    sms('stop');

    expect($citizen->fresh()->sms_notifications)->toBeFalse();
});

it('limits inbound messages per phone number', function () {
    foreach (range(1, 12) as $i) {
        sms('Hi')->assertOk();
    }

    sms('Hi')->assertStatus(429);
});

it('sends SMS through Africa\'s Talking when a key is set', function () {
    config(['services.africastalking.api_key' => 'at-key', 'services.africastalking.short_code' => '12345']);
    Http::fake([
        'api.sandbox.africastalking.com/*' => Http::response(['SMSMessageData' => ['Recipients' => [['status' => 'Success']]]]),
    ]);

    app(SmsGateway::class)->send('+254711222333', 'Test message');

    Http::assertSent(fn ($r) => $r->url() === 'https://api.sandbox.africastalking.com/version1/messaging'
        && $r->hasHeader('apiKey', 'at-key')
        && $r['to'] === '+254711222333'
        && $r['username'] === 'sandbox');
});
