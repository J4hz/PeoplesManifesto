<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RetrySubmissionRequest;
use App\Jobs\ProcessSubmission;
use App\Models\Submission;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Reports the model could not sort. Admins see the error and can send a
 * report back through intake. They cannot read, edit or remove it here.
 */
class IntakeController extends Controller
{
    public function index(): View
    {
        return view('admin.intake', [
            'submissions' => Submission::query()
                ->where('processing_status', '!=', Submission::STATUS_PROCESSED)
                ->where('created_at', '<', now()->subMinutes(10))
                ->with('ward')
                ->oldest()
                ->paginate(50),
        ]);
    }

    public function retry(RetrySubmissionRequest $request, Submission $submission, AuditLogger $audit): RedirectResponse
    {
        abort_if($submission->processing_status === Submission::STATUS_PROCESSED, 404);

        $submission->forceFill(['processing_status' => Submission::STATUS_PENDING, 'processing_error' => null])->save();
        ProcessSubmission::dispatch($submission->id);
        $audit->record('submission.retried', $submission);

        return back()->with('status', __('admin.intake.retried', ['id' => $submission->id]));
    }
}
