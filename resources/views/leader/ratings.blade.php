<x-layout.staff :title="__('leader.ratings.title')" active="ratings">
    <div class="page-head">
        <h1>{{ __('leader.ratings.title') }}</h1>
        <p>{{ __('leader.ratings.intro') }} <a href="{{ route('leaders.show', $leader) }}">{{ __('leader.ratings.public_record') }}</a></p>
    </div>

    <div class="stats">
        <x-stat-tile :value="$record['average_stars'] !== null ? number_format($record['average_stars'], 1) : __('civic.none_yet')" :label="__('public.leader.average_stars')" />
        <x-stat-tile :value="$record['confirmed_share'] !== null ? round($record['confirmed_share'] * 100).'%' : __('civic.none_yet')" :label="__('public.leader.confirmed_share')" />
        <x-stat-tile :value="$record['rated_fixes']" :label="__('public.leader.rated_fixes')" />
        <x-stat-tile :value="$record['ratings']" :label="__('public.leader.ratings')" />
    </div>

    <div class="grid-2">
        <section class="card stack">
            <h2>{{ __('public.leader.commendations_heading') }}</h2>
            @forelse ($record['commendations'] as $rating)
                <figure class="quote">
                    <blockquote>{{ $rating->thanks }}</blockquote>
                    <figcaption class="small muted">
                        @if ($rating->stars)<x-rating-stars :value="$rating->stars" />@endif
                        {{ __('public.leader.commendation_caption', ['issue' => $rating->cluster->title, 'ward' => $rating->cluster->ward->name, 'date' => $rating->created_at->translatedFormat('j M Y')]) }}
                    </figcaption>
                </figure>
            @empty
                <p class="muted">{{ __('public.leader.no_commendations') }}</p>
            @endforelse
        </section>

        <section class="card stack">
            <h2>{{ __('public.leader.praise_heading') }}</h2>
            <table>
                <tbody>
                    @foreach ($record['praise'] as $tag => $count)
                        <tr><td>{{ __('civic.praise.'.$tag) }}</td><td class="num">{{ $count }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </div>

    <section class="card stack">
        <h2>{{ __('leader.ratings.by_issue') }}</h2>
        @if ($clusters->isEmpty())
            <p class="muted">{{ __('leader.ratings.none') }}</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('leader.overview.col_issue') }}</th>
                            <th>{{ __('leader.overview.col_status') }}</th>
                            <th class="num">{{ __('civic.verdict.fixed') }}</th>
                            <th class="num">{{ __('civic.verdict.partly') }}</th>
                            <th class="num">{{ __('civic.verdict.not_fixed') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clusters as $cluster)
                            <tr>
                                <td><a href="{{ route('dashboard.clusters.show', $cluster) }}">{{ $cluster->title }}</a> <span class="small muted">{{ $cluster->ward->name }}</span></td>
                                <td><x-status-pill :status="$cluster->status" /></td>
                                <td class="num">{{ $cluster->fixed_count }}</td>
                                <td class="num">{{ $cluster->partly_count }}</td>
                                <td class="num">{{ $cluster->not_fixed_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $clusters->links('partials.pagination') }}
        @endif
    </section>
</x-layout.staff>
