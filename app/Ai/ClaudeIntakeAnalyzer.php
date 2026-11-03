<?php

namespace App\Ai;

use App\Ai\Contracts\IntakeAnalyzer;
use App\Models\Submission;
use Illuminate\Http\Client\RequestException;

class ClaudeIntakeAnalyzer implements IntakeAnalyzer
{
    public function __construct(
        private readonly AnthropicClient $client,
        private readonly UsageRecorder $usage,
    ) {}

    public function analyse(Submission $submission, array $wardNames, array $areaNames): IntakeResult
    {
        $model = config('ai.models.intake');

        try {
            $response = $this->client->message([
                'model' => $model,
                'max_tokens' => config('ai.intake.max_tokens'),
                // The model gets no tools. The fixed instructions are cached across every submission.
                'system' => [[
                    'type' => 'text',
                    'text' => IntakePrompt::system(),
                    'cache_control' => ['type' => 'ephemeral'],
                ]],
                'output_config' => [
                    'effort' => config('ai.intake.effort'),
                    'format' => ['type' => 'json_schema', 'schema' => IntakeSchema::jsonSchema()],
                ],
                'messages' => [[
                    'role' => 'user',
                    'content' => IntakePrompt::user($submission, $wardNames, $areaNames),
                ]],
            ]);
        } catch (RequestException $e) {
            throw new InvalidAiOutput('Intake request failed with HTTP '.$e->response->status(), previous: $e);
        }

        if (isset($response['usage']) && is_array($response['usage'])) {
            $this->usage->record('intake', $model, $response['usage'], $submission->ward_id);
        }

        $stop = $response['stop_reason'] ?? null;
        if ($stop !== 'end_turn') {
            throw new InvalidAiOutput('Intake stopped with reason: '.(is_string($stop) ? $stop : 'unknown'));
        }

        $text = collect($response['content'] ?? [])
            ->first(fn ($block) => is_array($block) && ($block['type'] ?? null) === 'text');

        if (! is_array($text) || ! is_string($text['text'] ?? null)) {
            throw new InvalidAiOutput('Intake response has no text block.');
        }

        try {
            $decoded = json_decode($text['text'], true, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidAiOutput('Intake response is not valid JSON.', previous: $e);
        }

        return IntakeSchema::validate($decoded, $wardNames, $areaNames);
    }
}
