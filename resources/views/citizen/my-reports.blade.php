<x-layout.citizen :title="__('citizen.reports.title')" active="reports">
    <div class="page-head">
        <h1>{{ __('citizen.reports.title') }}</h1>
        <p>{{ __('citizen.reports.intro') }}</p>
    </div>

    @if ($notifications->isNotEmpty())
        <section class="card stack">
            <h2><x-icon name="bell" /> {{ __('citizen.reports.news_heading') }}</h2>
            <ul class="timeline">
                @foreach ($notifications as $notification)
                    <li>
                        <div class="when">{{ $notification->created_at->diffForHumans() }}</div>
                        <p>{{ __('notifications.'.$notification->data['kind'], ['title' => $notification->data['title'] ?? '']) }}</p>
                        @if (! empty($notification->data['cluster_id']))
                            <a href="{{ route('clusters.show', $notification->data['cluster_id']) }}">{{ __('citizen.reports.open_issue') }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($submissions->isEmpty())
        <div class="card">
            <p>{{ __('citizen.reports.empty') }}</p>
            <a class="btn" href="{{ route('home') }}">{{ __('citizen.nav.raise') }}</a>
        </div>
    @else
        <ul class="issue-list">
            @foreach ($submissions as $submission)
                <li class="card card-tight stack">
                    <div class="row row-between">
                        <span class="muted small">{{ __('citizen.reports.sent_on', ['date' => $submission->created_at->translatedFormat('j M Y, H:i'), 'channel' => __('civic.channel.'.$submission->channel)]) }}</span>
                        @if ($submission->cluster)
                            <x-status-pill :status="$submission->cluster->status" />
                        @elseif ($submission->processing_status === 'failed')
                            <span class="pill">{{ __('citizen.reports.waiting') }}</span>
                        @else
                            <span class="pill">{{ __('citizen.reports.sorting') }}</span>
                        @endif
                    </div>
                    @if ($submission->isVoice())
                        <p>{{ __('citizen.reports.voice_on', ['title' => $submission->cluster?->title]) }}</p>
                    @else
                        <p>{{ $submission->body }}</p>
                    @endif
                    @if ($submission->photo_path)
                        <a href="{{ route('citizen.reports.photo', $submission) }}">{{ __('citizen.reports.view_photo') }}</a>
                    @endif
                    @if ($submission->cluster)
                        <p class="small">
                            {{ __('citizen.reports.grouped_into') }}
                            <a href="{{ route('clusters.show', $submission->cluster) }}">{{ $submission->cluster->title }}</a>
                            &middot; {{ trans_choice('civic.residents_count', $submission->cluster->resident_count, ['count' => $submission->cluster->resident_count]) }}
                        </p>
                    @endif
                </li>
            @endforeach
        </ul>
        {{ $submissions->links('partials.pagination') }}
    @endif
</x-layout.citizen>
