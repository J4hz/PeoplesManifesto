<?php

namespace App\Models;

use App\Services\IdentityHasher;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Citizen extends Authenticatable implements HasLocalePreference
{
    use HasFactory, Notifiable;

    protected $fillable = ['phone', 'phone_hash', 'ward_id', 'locale', 'trust_score', 'sms_notifications', 'verified_at', 'last_seen_at'];

    protected $hidden = ['phone', 'phone_hash', 'remember_token'];

    protected function casts(): array
    {
        return [
            'phone' => 'encrypted',
            'trust_score' => 'float',
            'sms_notifications' => 'boolean',
            'verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    /**
     * Keyed reference used in the identity and ratings tables instead of the id.
     */
    public function ref(): string
    {
        return app(IdentityHasher::class)->citizen($this->id);
    }

    public static function findByPhone(string $normalisedPhone): ?self
    {
        return static::where('phone_hash', app(IdentityHasher::class)->phone($normalisedPhone))->first();
    }

    /**
     * Submissions this citizen made, found through the identity table.
     */
    public function submissions(): Builder
    {
        return Submission::query()->whereIn(
            'id',
            SubmissionAuthor::where('citizen_ref', $this->ref())->select('submission_id')
        );
    }

    public function routeNotificationForSms(): ?string
    {
        return $this->sms_notifications ? $this->phone : null;
    }

    public function preferredLocale(): string
    {
        return $this->locale;
    }

    public function ageInDays(): int
    {
        return (int) $this->created_at->diffInDays(now());
    }
}
