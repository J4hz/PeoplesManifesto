<?php

namespace App\Actions;

use App\Jobs\ProcessSubmission;
use App\Models\Citizen;
use App\Models\Submission;
use App\Models\SubmissionAuthor;
use App\Services\PhotoStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateSubmission
{
    public function __construct(private readonly PhotoStore $photos) {}

    /**
     * @param  array{body: string, category?: ?string, severity?: ?string}  $data
     */
    public function handle(Citizen $citizen, array $data, ?UploadedFile $photo, string $channel): Submission
    {
        $photoPath = $photo ? $this->photos->store($photo, 'submissions') : null;

        $submission = DB::transaction(function () use ($citizen, $data, $photoPath, $channel) {
            $submission = Submission::create([
                'ward_id' => $citizen->ward_id,
                'kind' => Submission::KIND_REPORT,
                'channel' => $channel,
                'body' => trim($data['body']),
                'stated_category' => $data['category'] ?? null,
                'stated_severity' => $data['severity'] ?? null,
                'photo_path' => $photoPath,
                'processing_status' => Submission::STATUS_PENDING,
            ]);

            SubmissionAuthor::create([
                'submission_id' => $submission->id,
                'citizen_ref' => $citizen->ref(),
                'citizen_id' => (string) $citizen->id,
            ]);

            return $submission;
        });

        ProcessSubmission::dispatch($submission->id)->afterCommit();

        return $submission;
    }
}
