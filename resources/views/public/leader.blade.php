<x-layout.citizen :title="$leader->name">
    <div class="page-head">
        <h1>{{ $leader->name }}</h1>
        <p>{{ $leader->office->name }}</p>
    </div>

    <div class="stats">
        <x-stat-tile :value="$record['rated_fixes']" :label="__('public.leader.rated_fixes')" />
        <x-stat-tile :value="$record['average_stars'] !== null ? number_format($record['average_stars'], 1) : __('civic.none_yet')" :label="__('public.leader.average_stars')" />
        <x-stat-tile :value="$record['confirmed_share'] !== null ? round($record['confirmed_share'] * 100).'%' : __('civic.none_yet')" :label="__('public.leader.confirmed_share')" />
        <x-stat-tile :value="$record['ratings']" :label="__('public.leader.ratings')" />
    </div>

    <section class="card stack">
        <h2>{{ __('public.leader.praise_heading') }}</h2>
        <ul class="timeline">
            @foreach ($record['praise'] as $tag => $count)
                <li class="row row-between"><span>{{ __('civic.praise.'.$tag) }}</span><strong>{{ $count }}</strong></li>
            @endforeach
        </ul>
    </section>

    <section class="card stack">
        <h2>{{ __('public.leader.commendations_heading') }}</h2>
        @forelse ($record['commendations'] as $rating)
            <figure class="quote">
                <blockquote>{{ $rating->thanks }}</blockquote>
                <figcaption class="small muted">{{ __('public.leader.commendation_caption', ['issue' => $rating->cluster->title, 'ward' => $rating->cluster->ward->name, 'date' => $rating->created_at->translatedFormat('j M Y')]) }}</figcaption>
            </figure>
        @empty
            <p class="muted">{{ __('public.leader.no_commendations') }}</p>
        @endforelse
    </section>

    @if ($record['recent_done']->isNotEmpty())
        <section>
            <h2>{{ __('public.leader.recent_done') }}</h2>
            <ul class="issue-list">
                @foreach ($record['recent_done'] as $cluster)
                    <x-issue-card :cluster="$cluster" />
                @endforeach
            </ul>
        </section>
    @endif
</x-layout.citizen>
