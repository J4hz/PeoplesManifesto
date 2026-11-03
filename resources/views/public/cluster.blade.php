<x-layout.citizen :title="$cluster->title">
    <p class="small"><a href="{{ route('wards.show', $cluster->ward) }}"><x-icon name="arrow-left" /> {{ __('public.cluster.back', ['ward' => $cluster->ward->name]) }}</a></p>

    <section class="card stack">
        <div class="issue-meta">
            <x-status-pill :status="$cluster->status" />
            <x-severity-tag :severity="$cluster->severity" />
            <span>{{ __('civic.category.'.$cluster->category) }}</span>
        </div>
        <h1>{{ $cluster->title }}</h1>
        @if ($cluster->summary)
            <p>{{ $cluster->summary }}</p>
        @endif
        <div class="issue-meta">
            <span><x-icon name="users" /> {{ trans_choice('civic.residents_count', $cluster->resident_count, ['count' => $cluster->resident_count]) }}</span>
            @if ($cluster->areas_count > 0)
                <span><x-icon name="map-pin" /> {{ trans_choice('civic.areas_count', $cluster->areas_count, ['count' => $cluster->areas_count]) }}</span>
            @endif
            <span><x-icon name="calendar" /> {{ __('public.cluster.first_reported', ['date' => $cluster->first_reported_at?->translatedFormat('j M Y')]) }}</span>
            @if ($cluster->routedOffice)
                <span>{{ __('public.cluster.routed_to', ['office' => $cluster->routedOffice->name]) }}</span>
            @endif
        </div>

        @if ($citizen && $state)
            @if (Gate::forUser($citizen)->allows('addVoice', $cluster))
                @if ($state->reported($cluster))
                    <p class="tag"><x-icon name="check" /> {{ __('citizen.ward.counted') }}</p>
                @else
                    <form method="POST" action="{{ route('citizen.voice', $cluster) }}">
                        @csrf
                        <button type="submit" class="btn"><x-icon name="plus" /> {{ __('citizen.ward.add_voice') }}</button>
                    </form>
                @endif
            @endif
            @if (Gate::forUser($citizen)->allows('rate', $cluster))
                <a class="btn" href="{{ route('citizen.rate', $cluster) }}">{{ __('citizen.ward.rate_fix') }}</a>
            @endif
        @endif
    </section>

    @if ($cluster->status === 'reopened')
        <div class="notice notice-warn">{{ __('public.cluster.reopened_notice') }}</div>
    @endif

    @if ($cluster->done_round > 0)
        <section class="card">
            <h2>{{ __('public.cluster.ratings_heading') }}</h2>
            <p x-data="fixedCount" data-url="{{ route('clusters.fixed-count', $cluster) }}" data-fixed="{{ $fixedCount }}" data-total="{{ $totalCount }}" aria-live="polite">
                {{ __('citizen.rate.live_count_before') }}
                <strong x-ref="fixed">{{ $fixedCount }}</strong>
                {{ __('citizen.rate.live_count_middle') }}
                <strong x-ref="total">{{ $totalCount }}</strong>
                {{ __('citizen.rate.live_count_after') }}
            </p>
        </section>
    @endif

    <section class="card stack">
        <h2>{{ __('public.cluster.updates_heading') }}</h2>
        @if ($updates->isEmpty())
            <p class="muted">{{ __('public.cluster.no_updates') }}</p>
        @else
            <ol class="timeline">
                @foreach ($updates as $update)
                    <li class="stack">
                        <div class="row row-between">
                            <strong><a href="{{ route('leaders.show', $update->leader) }}">{{ $update->leader->name }}</a>, {{ $update->leader->office->name }}</strong>
                            <span class="when">{{ $update->published_at->translatedFormat('j M Y, H:i') }}</span>
                        </div>
                        @if ($update->status)
                            <div><x-status-pill :status="$update->status" /></div>
                        @endif
                        <p>{{ $update->message }}</p>
                        @if ($update->expected_completion_on)
                            <p class="small muted">{{ __('public.cluster.expected', ['date' => $update->expected_completion_on->translatedFormat('j M Y')]) }}</p>
                        @endif
                        @if ($update->photo_path)
                            <img src="{{ route('photos.update', $update) }}" alt="{{ __('public.cluster.update_photo_alt', ['leader' => $update->leader->name]) }}" loading="lazy">
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</x-layout.citizen>
