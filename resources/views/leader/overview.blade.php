<x-layout.staff :title="__('leader.overview.title')" active="overview">
    <div class="page-head">
        <h1>{{ $user->leader->name }}</h1>
        <p>{{ $user->leader->office->name }}</p>
    </div>

    <div class="stats">
        <x-stat-tile :value="number_format($stats['residents'])" :label="__('leader.overview.residents')" />
        <x-stat-tile :value="number_format($stats['open'])" :label="__('leader.overview.open')" />
        <x-stat-tile :value="$stats['response_days'] === null ? __('civic.none_yet') : trans_choice('leader.overview.days', $stats['response_days'], ['count' => $stats['response_days']])" :label="__('leader.overview.response')" />
        <x-stat-tile :value="number_format($stats['resolved'])" :label="__('leader.overview.resolved')" />
    </div>

    <section class="card stack">
        <h2>{{ __('leader.overview.brief_heading') }}</h2>
        @forelse ($briefs as $brief)
            <details @if ($loop->first) open @endif>
                <summary><strong>{{ $brief->ward->name }}</strong> <span class="muted">{{ __('leader.overview.week_of', ['date' => $brief->week_start->translatedFormat('j M Y')]) }}</span></summary>
                <div class="stack">
                    <p class="brief">{{ $brief->body }}</p>
                    <div class="grid-2">
                        <div>
                            <h3><x-icon name="trending-up" /> {{ __('leader.overview.rising') }}</h3>
                            <ul>
                                @forelse ($brief->rising_topics as $topic)
                                    <li>{{ $topic }}</li>
                                @empty
                                    <li class="muted">{{ __('leader.overview.nothing') }}</li>
                                @endforelse
                            </ul>
                        </div>
                        <div>
                            <h3><x-icon name="trending-down" /> {{ __('leader.overview.improving') }}</h3>
                            <ul>
                                @forelse ($brief->improving_topics as $topic)
                                    <li>{{ $topic }}</li>
                                @empty
                                    <li class="muted">{{ __('leader.overview.nothing') }}</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                    @if ($brief->source === 'template')
                        <p class="small muted">{{ __('leader.overview.template_brief') }}</p>
                    @endif
                </div>
            </details>
        @empty
            <p class="muted">{{ __('leader.overview.no_brief') }}</p>
        @endforelse
    </section>

    <section class="card stack">
        <div class="section-title">
            <h2>{{ __('leader.overview.issues_heading') }}</h2>
        </div>
        <form method="GET" action="{{ route('dashboard') }}" class="row">
            @if ($wards->count() > 1)
                <label for="ward" class="visually-hidden">{{ __('leader.overview.ward_filter') }}</label>
                <select id="ward" name="ward">
                    <option value="">{{ __('leader.overview.all_wards') }}</option>
                    @foreach ($wards as $ward)
                        <option value="{{ $ward->id }}" @selected($wardFilter === $ward->id)>{{ $ward->name }}</option>
                    @endforeach
                </select>
            @endif
            <label class="checkbox">
                <input type="checkbox" name="routed" value="1" @checked($routedOnly)>
                <span>{{ __('leader.overview.routed_only') }}</span>
            </label>
            <button type="submit" class="btn btn-quiet">{{ __('leader.overview.filter') }}</button>
        </form>

        @if ($clusters->isEmpty())
            <p class="muted">{{ __('leader.overview.no_issues') }}</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th class="num">{{ __('leader.overview.col_rank') }}</th>
                            <th>{{ __('leader.overview.col_issue') }}</th>
                            <th class="num">{{ __('leader.overview.col_residents') }}</th>
                            <th>{{ __('leader.overview.col_severity') }}</th>
                            <th class="num">{{ __('leader.overview.col_areas') }}</th>
                            <th>{{ __('leader.overview.col_trend') }}</th>
                            <th>{{ __('leader.overview.col_status') }}</th>
                            <th><span class="visually-hidden">{{ __('leader.overview.col_action') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clusters as $i => $cluster)
                            <tr>
                                <td class="num">{{ $clusters->firstItem() + $i }}</td>
                                <td>
                                    <strong>{{ $cluster->title }}</strong>
                                    <div class="small muted">{{ __('civic.category.'.$cluster->category) }}@if ($wards->count() > 1) &middot; {{ $cluster->ward->name }}@endif</div>
                                </td>
                                <td class="num">{{ $cluster->resident_count }}</td>
                                <td><x-severity-tag :severity="$cluster->severity" /></td>
                                <td class="num">{{ $cluster->areas_count }}</td>
                                <td class="nowrap">
                                    @if ($cluster->trend === 'rising')<x-icon name="trending-up" />@elseif ($cluster->trend === 'falling')<x-icon name="trending-down" />@endif
                                    {{ __('civic.trend.'.$cluster->trend) }}
                                </td>
                                <td><x-status-pill :status="$cluster->status" /></td>
                                <td><a class="btn btn-small" href="{{ route('dashboard.clusters.show', $cluster) }}">{{ __('leader.overview.respond') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $clusters->links('partials.pagination') }}
        @endif
    </section>
</x-layout.staff>
