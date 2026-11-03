<?php

use App\Ai\AnthropicClient;
use App\Ai\SpendGuard;
use App\Ai\TemplateBriefWriter;
use App\Ai\UsageRecorder;
use App\Jobs\DispatchWeeklyBriefs;
use App\Jobs\PollBriefBatches;
use App\Models\AiBatch;
use App\Models\AiUsageLog;
use App\Models\Ward;
use App\Models\WeeklyBrief;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => pilot());

function dispatchBriefs(): void
{
    (new DispatchWeeklyBriefs)->handle(app(AnthropicClient::class), app(SpendGuard::class), app(TemplateBriefWriter::class));
}

function pollBriefs(): void
{
    (new PollBriefBatches)->handle(app(AnthropicClient::class), app(UsageRecorder::class), app(TemplateBriefWriter::class));
}

it('writes template briefs for every active ward when there is no key', function () {
    Http::fake();

    dispatchBriefs();

    expect(WeeklyBrief::count())->toBe(Ward::active()->count())
        ->and(WeeklyBrief::first()->source)->toBe('template');
    Http::assertNothingSent();
});

it('sends one Fable batch for all wards and stores the results', function () {
    config(['ai.anthropic.key' => 'test-key']);
    clusterWithReporters([citizen(), citizen()], ['title' => 'No water in Kiuu']);

    Http::fake([
        'api.anthropic.com/v1/messages/batches' => Http::response(['id' => 'msgbatch_123', 'processing_status' => 'in_progress']),
    ]);

    dispatchBriefs();

    $batch = AiBatch::sole();
    expect($batch->provider_batch_id)->toBe('msgbatch_123')
        ->and(count($batch->request_map))->toBe(Ward::active()->count());

    Http::assertSent(function (Request $request) {
        $first = $request['requests'][0]['params'];

        return $request->url() === 'https://api.anthropic.com/v1/messages/batches'
            && count($request['requests']) === 8
            && $first['model'] === 'claude-fable-5-1'
            && $first['system'][0]['cache_control'] === ['type' => 'ephemeral']
            && $first['output_config']['format']['type'] === 'json_schema'
            && ! array_key_exists('tools', $first)
            && ! array_key_exists('fallbacks', $first);
    });

    $kiuuId = 'ward-'.ward('kiuu')->id.'-'.now()->startOfWeek()->subWeek()->format('Ymd');
    $mwikiId = 'ward-'.ward('mwiki')->id.'-'.now()->startOfWeek()->subWeek()->format('Ymd');
    $good = ['brief' => 'Water is the main issue in Kiuu this week, raised by two residents. Post an update on the pipe repair.', 'rising_topics' => ['No water in Kiuu'], 'improving_topics' => []];

    $line = fn (string $id, array|string $payload, string $type = 'succeeded') => json_encode([
        'custom_id' => $id,
        'result' => $type === 'succeeded' ? ['type' => 'succeeded', 'message' => [
            'model' => 'claude-fable-5-1',
            'stop_reason' => 'end_turn',
            'content' => [['type' => 'text', 'text' => is_string($payload) ? $payload : json_encode($payload)]],
            'usage' => ['input_tokens' => 1000, 'output_tokens' => 300, 'cache_read_input_tokens' => 2000, 'cache_creation_input_tokens' => 0],
        ]] : ['type' => $type],
    ]);

    Http::fake([
        'api.anthropic.com/v1/messages/batches/msgbatch_123' => Http::response(['id' => 'msgbatch_123', 'processing_status' => 'ended', 'results_url' => 'https://api.anthropic.com/v1/messages/batches/msgbatch_123/results']),
        'api.anthropic.com/v1/messages/batches/msgbatch_123/results' => Http::response($line($mwikiId, 'not json at all')."\n".$line($kiuuId, $good)."\n"),
    ]);

    pollBriefs();

    $kiuu = WeeklyBrief::where('ward_id', ward('kiuu')->id)->sole();
    expect($kiuu->source)->toBe('ai')
        ->and($kiuu->rising_topics)->toBe(['No water in Kiuu'])
        ->and(WeeklyBrief::where('ward_id', ward('mwiki')->id)->sole()->source)->toBe('template')
        ->and(WeeklyBrief::count())->toBe(8)
        ->and($batch->fresh()->status)->toBe('processed');

    $usage = AiUsageLog::where('job', 'weekly_brief')->get();
    expect($usage)->toHaveCount(2)
        ->and($usage->every->is_batch)->toBeTrue()
        // Batch price: half of (1000 * 10 + 300 * 50 + 2000 * 0.25) per million.
        ->and($usage->first()->cost_usd)->toBe(0.01275);
});

it('pauses model briefs once the monthly cap is reached', function () {
    config(['ai.anthropic.key' => 'test-key', 'ai.spend.monthly_cap_usd' => 10]);
    AiUsageLog::create(['job' => 'intake', 'model' => 'claude-haiku-5-5', 'cost_usd' => 10.5]);
    Http::fake();

    dispatchBriefs();

    Http::assertNothingSent();
    expect(AiBatch::count())->toBe(0)
        ->and(WeeklyBrief::first()->source)->toBe('template');
});

it('does not write a second brief for the same ward and week', function () {
    dispatchBriefs();
    dispatchBriefs();

    expect(WeeklyBrief::count())->toBe(Ward::active()->count());
});

it('shows the latest brief on the leader overview', function () {
    dispatchBriefs();

    $this->actingAs(leaderUser())->get('/dashboard')->assertOk()->assertSee('Kiuu')->assertSee('Weekly brief');
});
