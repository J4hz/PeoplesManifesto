<?php

namespace App\Http\Controllers;

use App\Models\Citizen;
use App\Models\ClusterUpdate;
use App\Models\Submission;
use App\Models\User;
use App\Services\PhotoStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Photos live on the private disk, outside the web root. Every request goes
 * through a policy check before the file is streamed.
 */
class PhotoController extends Controller
{
    public function __construct(private readonly PhotoStore $photos) {}

    public function update(Request $request, ClusterUpdate $update): StreamedResponse
    {
        Gate::forUser($request->user('web'))->authorize('view', $update);
        abort_unless($this->photos->exists($update->photo_path), 404);

        return $this->send($update->photo_path, $update->isPublished());
    }

    public function citizenSubmission(Request $request, Submission $submission): StreamedResponse
    {
        return $this->submissionFor($request->user('citizen'), $submission);
    }

    public function leaderSubmission(Request $request, Submission $submission): StreamedResponse
    {
        return $this->submissionFor($request->user('web'), $submission);
    }

    /**
     * Each route checks the identity of its own area, so a leader who is also a
     * resident is never judged by the wrong account.
     */
    private function submissionFor(User|Citizen|null $actor, Submission $submission): StreamedResponse
    {
        abort_if($actor === null, 403);

        Gate::forUser($actor)->authorize('viewPhoto', $submission);
        abort_unless($this->photos->exists($submission->photo_path), 404);

        return $this->send($submission->photo_path, false);
    }

    private function send(string $path, bool $public): StreamedResponse
    {
        return $this->photos->response($path, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => $public ? 'public, max-age=86400' : 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
