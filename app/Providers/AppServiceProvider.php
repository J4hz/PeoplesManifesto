<?php

namespace App\Providers;

use App\Ai\AnthropicClient;
use App\Ai\ClaudeClusterSummarizer;
use App\Ai\ClaudeIntakeAnalyzer;
use App\Ai\Contracts\ClusterSummarizer;
use App\Ai\Contracts\IntakeAnalyzer;
use App\Ai\LocalClusterSummarizer;
use App\Ai\LocalIntakeAnalyzer;
use App\Ai\UsageRecorder;
use App\Embeddings\Embedder;
use App\Embeddings\HashingEmbedder;
use App\Embeddings\VoyageEmbedder;
use App\Models\Area;
use App\Models\Citizen;
use App\Models\ClusterUpdate;
use App\Models\Constituency;
use App\Models\County;
use App\Models\IssueCluster;
use App\Models\Leader;
use App\Models\Office;
use App\Models\Rating;
use App\Models\Submission;
use App\Models\User;
use App\Models\Ward;
use App\Services\IdentityHasher;
use App\Sms\AfricasTalkingGateway;
use App\Sms\LogSmsGateway;
use App\Sms\SmsChannel;
use App\Sms\SmsGateway;
use App\Support\PhoneNumber;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(IdentityHasher::class, function ($app) {
            $key = (string) config('auth.identity_hash_key');

            if ($key === '') {
                if ($app->environment('production')) {
                    throw new RuntimeException('IDENTITY_HASH_KEY must be set in production.');
                }
                // Local and test runs derive a key so the app starts without extra setup.
                $key = hash_hmac('sha256', 'identity-hash', (string) config('app.key'));
            }

            return new IdentityHasher($key);
        });

        $this->app->singleton(AnthropicClient::class, fn () => new AnthropicClient(
            apiKey: (string) config('ai.anthropic.key'),
            baseUrl: (string) config('ai.anthropic.base_url'),
            version: (string) config('ai.anthropic.version'),
            timeout: (int) config('ai.anthropic.timeout'),
        ));

        // With no key the app falls back to local stand-ins so it still runs end to end.
        $this->app->bind(IntakeAnalyzer::class, fn ($app) => filled(config('ai.anthropic.key'))
            ? $app->make(ClaudeIntakeAnalyzer::class)
            : $app->make(LocalIntakeAnalyzer::class));

        $this->app->bind(ClusterSummarizer::class, fn ($app) => filled(config('ai.anthropic.key'))
            ? $app->make(ClaudeClusterSummarizer::class)
            : $app->make(LocalClusterSummarizer::class));

        $this->app->bind(Embedder::class, fn ($app) => filled(config('ai.embeddings.key'))
            ? new VoyageEmbedder(
                apiKey: (string) config('ai.embeddings.key'),
                baseUrl: (string) config('ai.embeddings.base_url'),
                modelName: (string) config('ai.embeddings.model'),
                dimensions: (int) config('ai.embeddings.dimensions'),
                usage: $app->make(UsageRecorder::class),
            )
            : new HashingEmbedder((int) config('ai.embeddings.dimensions')));

        $this->app->bind(SmsGateway::class, fn () => filled(config('services.africastalking.api_key'))
            ? new AfricasTalkingGateway(
                username: (string) config('services.africastalking.username'),
                apiKey: (string) config('services.africastalking.api_key'),
                from: config('services.africastalking.sender_id') ?: config('services.africastalking.short_code'),
                sandbox: (bool) config('services.africastalking.sandbox'),
            )
            : new LogSmsGateway);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        if (filled(config('app.trusted_proxies'))) {
            $proxies = config('app.trusted_proxies');
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        Relation::enforceMorphMap([
            'county' => County::class,
            'constituency' => Constituency::class,
            'ward' => Ward::class,
            'area' => Area::class,
            'office' => Office::class,
            'leader' => Leader::class,
            'user' => User::class,
            'citizen' => Citizen::class,
            'submission' => Submission::class,
            'cluster' => IssueCluster::class,
            'cluster_update' => ClusterUpdate::class,
            'rating' => Rating::class,
        ]);

        Route::model('cluster', IssueCluster::class);
        Route::model('update', ClusterUpdate::class);

        Notification::resolved(function (ChannelManager $manager) {
            $manager->extend('sms', fn ($app) => $app->make(SmsChannel::class));
        });

        $this->defineGates();
        $this->defineRateLimits();
    }

    private function defineGates(): void
    {
        Gate::define('admin', fn (User $user) => $user->is_active && $user->isAdmin());
        Gate::define('leader-dashboard', fn (User $user) => $user->is_active && $user->worksForLeader());
        Gate::define('viewHorizon', fn (?User $user = null) => $user !== null && $user->is_active && $user->isAdmin());
    }

    /**
     * Every limit applies twice: once per phone number (or citizen) and once per IP,
     * so neither a shared cyber cafe IP nor a single number can flood the system.
     */
    private function defineRateLimits(): void
    {
        RateLimiter::for('otp', function (Request $request) {
            $phone = self::phoneKey($request->input('phone'));

            return [
                Limit::perMinutes(10, 3)->by('otp-phone:'.$phone),
                Limit::perHour(10)->by('otp-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('otp-verify', fn (Request $request) => [
            Limit::perMinutes(10, 10)->by('otp-verify-ip:'.$request->ip()),
        ]);

        RateLimiter::for('submissions', function (Request $request) {
            $citizen = $request->user('citizen');

            return [
                Limit::perHour(6)->by('sub-citizen:'.($citizen?->id ?? 'guest')),
                Limit::perHour(30)->by('sub-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('voices', function (Request $request) {
            $citizen = $request->user('citizen');

            return [
                Limit::perHour(30)->by('voice-citizen:'.($citizen?->id ?? 'guest')),
                Limit::perHour(120)->by('voice-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('ratings', function (Request $request) {
            $citizen = $request->user('citizen');

            return [
                Limit::perHour(20)->by('rating-citizen:'.($citizen?->id ?? 'guest')),
                Limit::perHour(60)->by('rating-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('sms-inbound', fn (Request $request) => [
            Limit::perMinutes(10, 12)->by('sms-phone:'.self::phoneKey($request->input('from'))),
        ]);
    }

    private static function phoneKey(mixed $input): string
    {
        $phone = PhoneNumber::normalise(is_string($input) ? $input : null);

        return $phone === null ? 'invalid' : app(IdentityHasher::class)->phone($phone);
    }
}
