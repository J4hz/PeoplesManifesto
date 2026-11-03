<?php

namespace App\Ai;

use App\Models\AiUsageLog;

class UsageRecorder
{
    /**
     * @param  array<string, mixed>  $usage  the "usage" object from an API response
     */
    public function record(string $job, string $model, array $usage, ?int $wardId = null, bool $batch = false): AiUsageLog
    {
        $input = (int) ($usage['input_tokens'] ?? 0);
        $output = (int) ($usage['output_tokens'] ?? 0);
        $cacheWrite = (int) ($usage['cache_creation_input_tokens'] ?? 0);
        $cacheRead = (int) ($usage['cache_read_input_tokens'] ?? 0);

        return AiUsageLog::create([
            'job' => $job,
            'model' => $model,
            'ward_id' => $wardId,
            'input_tokens' => $input,
            'output_tokens' => $output,
            'cache_creation_tokens' => $cacheWrite,
            'cache_read_tokens' => $cacheRead,
            'is_batch' => $batch,
            'cost_usd' => $this->cost($model, $input, $output, $cacheWrite, $cacheRead, $batch),
        ]);
    }

    public function cost(string $model, int $input, int $output, int $cacheWrite = 0, int $cacheRead = 0, bool $batch = false): float
    {
        $prices = config("ai.pricing.{$model}");
        if (! is_array($prices)) {
            return 0.0;
        }

        $cost = ($input * $prices['input']
            + $output * $prices['output']
            + $cacheWrite * $prices['cache_write']
            + $cacheRead * $prices['cache_read']) / 1_000_000;

        return round($batch ? $cost * (float) config('ai.batch_multiplier') : $cost, 6);
    }
}
