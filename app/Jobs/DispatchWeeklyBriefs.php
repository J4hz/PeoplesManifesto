<?php

namespace App\Jobs;

use App\Ai\AnthropicClient;
use App\Ai\BriefInput;
use App\Ai\BriefPrompt;
use App\Ai\SpendGuard;
use App\Ai\TemplateBriefWriter;
use App\Models\AiBatch;
use App\Models\Ward;
use App\Models\WeeklyBrief;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Monday morning: one Fable request per active ward, sent together through
 * the Message Batches API at half the normal price. Never one call per report.
 */
class DispatchWeeklyBriefs implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue('briefs');
    }

    public function handle(AnthropicClient $client, SpendGuard $spend, TemplateBriefWriter $template): void
    {
        $weekStart = now()->startOfWeek()->subWeek();

        $wards = Ward::active()
            ->whereDoesntHave('briefs', fn ($q) => $q->whereDate('week_start', $weekStart))
            ->get();

        if ($wards->isEmpty()) {
            return;
        }

        $useModel = filled(config('ai.anthropic.key'));

        if ($useModel && ! $spend->briefsAllowed()) {
            Log::warning('Weekly briefs paused: monthly AI spend cap reached.', ['cap' => $spend->cap()]);
            $useModel = false;
        }

        if (! $useModel) {
            foreach ($wards as $ward) {
                $input = BriefInput::for($ward, $weekStart);
                $this->store($ward, $weekStart, $template->write($input), $input, 'template');
            }

            return;
        }

        $requests = [];
        $map = [];
        foreach ($wards as $ward) {
            $customId = 'ward-'.$ward->id.'-'.$weekStart->format('Ymd');
            $requests[] = ['custom_id' => $customId, 'params' => BriefPrompt::params(BriefInput::for($ward, $weekStart))];
            $map[$customId] = $ward->id;
        }

        $batch = $client->createBatch($requests);

        AiBatch::create([
            'provider_batch_id' => $batch['id'],
            'kind' => 'weekly_brief',
            'status' => 'submitted',
            'week_start' => $weekStart->toDateString(),
            'request_map' => $map,
        ]);
    }

    /**
     * @param  array{brief: string, rising_topics: list<string>, improving_topics: list<string>}  $brief
     * @param  array<string, mixed>  $input
     */
    public static function store(Ward $ward, CarbonInterface $weekStart, array $brief, array $input, string $source, ?string $model = null, ?int $batchId = null): WeeklyBrief
    {
        return WeeklyBrief::updateOrCreate(
            ['ward_id' => $ward->id, 'week_start' => $weekStart->toDateString()],
            [
                'body' => $brief['brief'],
                'rising_topics' => $brief['rising_topics'],
                'improving_topics' => $brief['improving_topics'],
                'stats' => $input,
                'source' => $source,
                'model' => $model,
                'ai_batch_id' => $batchId,
            ],
        );
    }
}
