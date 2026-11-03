<?php

namespace App\Ai;

/**
 * Writes a plain, factual brief from the same numbers when there is no API
 * key, when the monthly cap is reached, or when a batch item fails. Leaders
 * still get their Monday summary, just without the model's reading of it.
 */
class TemplateBriefWriter
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{brief: string, rising_topics: list<string>, improving_topics: list<string>}
     */
    public function write(array $input): array
    {
        $lines = [];

        if ($input['reports_this_week'] === 0) {
            $lines[] = "No reports came in from {$input['ward']} this week.";
        } else {
            $lines[] = "{$input['ward']} sent {$input['reports_this_week']} reports this week, against {$input['reports_last_week']} the week before.";
        }

        $top = $input['top_open_issues'][0] ?? null;
        if ($top !== null) {
            $lines[] = "The top open issue is \"{$top['title']}\", raised by {$top['residents']} residents and marked {$top['severity']}.";
        }

        if ($input['new_issues'] !== []) {
            $lines[] = 'New this week: '.collect($input['new_issues'])->pluck('title')->map(fn ($t) => "\"{$t}\"")->implode(', ').'.';
        }

        if ($input['reopened_by_residents_this_week'] !== []) {
            $lines[] = 'Residents said these were not fixed and they were reopened: '
                .collect($input['reopened_by_residents_this_week'])->pluck('title')->map(fn ($t) => "\"{$t}\"")->implode(', ').'.';
        }

        if (count($input['marked_done_this_week']) > 0) {
            $lines[] = count($input['marked_done_this_week']).' issues were marked done this week and are waiting for residents to rate the work.';
        }

        return [
            'brief' => implode("\n\n", $lines),
            'rising_topics' => collect($input['rising_issues'])->pluck('title')->take(5)->values()->all(),
            // Only work leaders finished counts as improving. An issue that went quiet may simply be ignored.
            'improving_topics' => collect($input['marked_done_this_week'])->pluck('title')->unique()->take(5)->values()->all(),
        ];
    }
}
