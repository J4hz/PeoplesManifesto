<?php

namespace App\Sms;

use App\Actions\CreateSubmission;
use App\Actions\RecordRating;
use App\Models\Citizen;
use App\Models\IssueCluster;
use App\Models\Rating;
use App\Models\SmsSession;
use App\Models\SubmissionAuthor;
use App\Models\Ward;
use App\Services\IdentityHasher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * The SMS version of raising an issue, one message at a time:
 * ward (first time only), description, severity by number, confirmation.
 * A bare 1, 2 or 3 outside a conversation answers the latest "was it fixed" question.
 */
class SmsConversation
{
    private const SEVERITIES = ['1' => 'annoying', '2' => 'serious', '3' => 'urgent'];

    private const VERDICTS = ['1' => Rating::FIXED, '2' => Rating::PARTLY, '3' => Rating::NOT_FIXED];

    public function __construct(
        private readonly IdentityHasher $hasher,
        private readonly CreateSubmission $createSubmission,
        private readonly RecordRating $recordRating,
    ) {}

    public function handle(string $phone, string $text): ?string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        $keyword = Str::upper($text);
        $hash = $this->hasher->phone($phone);
        $citizen = Citizen::where('phone_hash', $hash)->first();
        $session = SmsSession::where('phone_hash', $hash)->where('expires_at', '>', now())->first();
        $locale = $citizen?->locale ?? ($session?->data['locale'] ?? 'en');

        if (in_array($keyword, ['SW', 'EN'], true)) {
            $locale = Str::lower($keyword);
            $citizen?->forceFill(['locale' => $locale])->save();

            if ($session === null) {
                return $this->start($hash, $citizen, '', $locale);
            }

            $session = $this->save($hash, $session->step, array_merge($session->data, ['locale' => $locale]));

            return $this->prompt($session, $citizen, $locale);
        }

        if ($keyword === 'STOP') {
            $session?->delete();
            $citizen?->forceFill(['sms_notifications' => false])->save();

            return __('sms.stopped', [], $locale);
        }

        if ($session === null && isset(self::VERDICTS[$text]) && $citizen !== null) {
            $cluster = $this->pendingRating($citizen);
            if ($cluster !== null) {
                $this->recordRating->handle($citizen, $cluster, ['verdict' => self::VERDICTS[$text]], 'sms');

                return __('sms.rating_thanks', [], $locale);
            }
        }

        return match ($session?->step) {
            'ward' => $this->chooseWard($hash, $session, $citizen, $phone, $text, $locale),
            'describe' => $this->describe($hash, $session, $text, $locale),
            'severity' => $this->severity($hash, $session, $citizen, $text, $locale),
            default => $this->start($hash, $citizen, $text, $locale),
        };
    }

    private function start(string $hash, ?Citizen $citizen, string $text, string $locale): string
    {
        // A first message that already describes the problem is kept, so nobody has to type it twice.
        $body = mb_strlen($text) >= 15 ? $text : null;

        if ($citizen === null || $citizen->ward_id === null) {
            $session = $this->save($hash, 'ward', ['locale' => $locale, 'body' => $body, 'wards' => $this->wards()->pluck('id')->all()]);

            return $this->prompt($session, $citizen, $locale);
        }

        $session = $this->save($hash, $body === null ? 'describe' : 'severity', ['locale' => $locale, 'body' => $body]);

        return $this->prompt($session, $citizen, $locale);
    }

    private function chooseWard(string $hash, SmsSession $session, ?Citizen $citizen, string $phone, string $text, string $locale): string
    {
        $wardIds = $session->data['wards'] ?? [];
        $index = ctype_digit($text) ? (int) $text - 1 : -1;

        if (! isset($wardIds[$index])) {
            return __('sms.invalid_ward', ['list' => $this->wardList($wardIds)], $locale);
        }

        // Sending from the number proves the resident holds it, the same as an OTP would.
        $citizen ??= Citizen::create([
            'phone' => $phone,
            'phone_hash' => $hash,
            'locale' => $locale,
            'verified_at' => now(),
        ]);
        $citizen->forceFill(['ward_id' => $wardIds[$index]])->save();

        $body = $session->data['body'] ?? null;
        $session = $this->save($hash, $body === null ? 'describe' : 'severity', ['locale' => $locale, 'body' => $body]);

        return $this->prompt($session, $citizen, $locale);
    }

    private function describe(string $hash, SmsSession $session, string $text, string $locale): string
    {
        if (mb_strlen($text) < 8) {
            return __('sms.too_short', [], $locale);
        }

        $session = $this->save($hash, 'severity', array_merge($session->data, ['body' => $text]));

        return $this->prompt($session, null, $locale);
    }

    private function severity(string $hash, SmsSession $session, ?Citizen $citizen, string $text, string $locale): string
    {
        if (! isset(self::SEVERITIES[$text]) || $citizen === null) {
            return __('sms.severity_prompt', [], $locale);
        }

        $limiterKey = 'sms-submission:'.$hash;
        if (RateLimiter::tooManyAttempts($limiterKey, 6)) {
            return __('sms.too_many', [], $locale);
        }
        RateLimiter::hit($limiterKey, 3600);

        $submission = $this->createSubmission->handle($citizen, [
            'body' => (string) $session->data['body'],
            'severity' => self::SEVERITIES[$text],
        ], null, 'sms');

        $session->delete();

        return __('sms.confirmed', ['id' => $submission->id], $locale);
    }

    private function prompt(SmsSession $session, ?Citizen $citizen, string $locale): string
    {
        return match ($session->step) {
            'ward' => __('sms.ward_prompt', ['list' => $this->wardList($session->data['wards'] ?? [])], $locale),
            'severity' => __('sms.severity_prompt', [], $locale),
            default => __('sms.describe_prompt', [], $locale),
        };
    }

    /**
     * The most recent Done issue this resident is counted on and has not yet rated.
     */
    private function pendingRating(Citizen $citizen): ?IssueCluster
    {
        $ref = $citizen->ref();

        return IssueCluster::query()
            ->where('status', IssueCluster::STATUS_DONE)
            ->whereNull('rating_window_closed_at')
            ->whereHas('memberships', fn ($q) => $q->whereIn(
                'submission_id',
                SubmissionAuthor::where('citizen_ref', $ref)->select('submission_id'),
            ))
            ->whereNotExists(fn ($q) => $q->from('ratings')
                ->whereColumn('ratings.cluster_id', 'issue_clusters.id')
                ->whereColumn('ratings.round', 'issue_clusters.done_round')
                ->where('ratings.citizen_ref', $ref))
            ->latest('done_at')
            ->first();
    }

    /**
     * @return Collection<int, Ward>
     */
    private function wards(): Collection
    {
        return Ward::active()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @param  list<int>  $wardIds
     */
    private function wardList(array $wardIds): string
    {
        $names = Ward::whereIn('id', $wardIds)->pluck('name', 'id');

        return collect($wardIds)->map(fn ($id, $i) => ($i + 1).' '.($names[$id] ?? ''))->implode(', ');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function save(string $hash, string $step, array $data): SmsSession
    {
        return SmsSession::updateOrCreate(
            ['phone_hash' => $hash],
            ['step' => $step, 'data' => $data, 'expires_at' => now()->addMinutes((int) config('civic.sms.session_minutes'))],
        );
    }
}
