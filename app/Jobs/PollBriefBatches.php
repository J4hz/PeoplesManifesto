<?php

namespace App\Jobs;

use App\Ai\AnthropicClient;
use App\Ai\BriefInput;
use App\Ai\BriefSchema;
use App\Ai\InvalidAiOutput;
use App\Ai\TemplateBriefWriter;
use App\Ai\UsageRecorder;
use App\Models\AiBatch;
use App\Models\Ward;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PollBriefBatches implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue('briefs');
    }

    public function handle(AnthropicClient $client, UsageRecorder $usage, TemplateBriefWriter $template): void
    {
        AiBatch::where('kind', 'weekly_brief')->where('status', 'submitted')->each(function (AiBatch $batch) use ($client, $usage, $template) {
            $remote = $client->retrieveBatch($batch->provider_batch_id);

            if (($remote['processing_status'] ?? null) !== 'ended') {
                return;
            }

            $batch->forceFill(['status' => 'ended', 'ended_at' => now()])->save();

            $results = collect($client->batchResults((string) $remote['results_url']))->keyBy('custom_id');
            $weekStart = $batch->week_start->copy();

            // Results arrive in any order, so every ward is looked up by its custom_id.
            foreach ($batch->request_map as $customId => $wardId) {
                $ward = Ward::find($wardId);
                if ($ward === null) {
                    continue;
                }

                $input = BriefInput::for($ward, $weekStart);
                $row = $results->get($customId);

                try {
                    $brief = $this->parse($row, $usage, $ward);
                    DispatchWeeklyBriefs::store($ward, $weekStart, $brief, $input, 'ai', config('ai.models.brief'), $batch->id);
                } catch (InvalidAiOutput $e) {
                    Log::warning('Weekly brief from the model was not usable, using the template.', ['ward' => $ward->id, 'reason' => $e->getMessage()]);
                    DispatchWeeklyBriefs::store($ward, $weekStart, $template->write($input), $input, 'template', null, $batch->id);
                }
            }

            $batch->forceFill(['status' => 'processed', 'processed_at' => now()])->save();
        });
    }

    /**
     * @param  array<string, mixed>|null  $row
     * @return array{brief: string, rising_topics: list<string>, improving_topics: list<string>}
     */
    private function parse(?array $row, UsageRecorder $usage, Ward $ward): array
    {
        if ($row === null || ($row['result']['type'] ?? null) !== 'succeeded') {
            throw new InvalidAiOutput('Batch item did not succeed: '.($row['result']['type'] ?? 'missing'));
        }

        $message = $row['result']['message'];

        if (isset($message['usage']) && is_array($message['usage'])) {
            $usage->record('weekly_brief', (string) ($message['model'] ?? config('ai.models.brief')), $message['usage'], $ward->id, batch: true);
        }

        if (($message['stop_reason'] ?? null) !== 'end_turn') {
            throw new InvalidAiOutput('Brief stopped with reason: '.($message['stop_reason'] ?? 'unknown'));
        }

        $text = collect($message['content'] ?? [])->first(fn ($b) => is_array($b) && ($b['type'] ?? null) === 'text');
        $decoded = is_array($text) ? json_decode((string) ($text['text'] ?? ''), true) : null;

        return BriefSchema::validate($decoded);
    }
}
