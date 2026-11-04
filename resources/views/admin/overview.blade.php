<x-layout.staff :title="__('admin.overview.title')" active="admin">
    <div class="page-head">
        <h1>{{ __('admin.overview.title') }}</h1>
        <p>{{ __('admin.overview.intro') }}</p>
    </div>

    <div class="stats">
        <x-stat-tile :value="number_format($citizens)" :label="__('admin.overview.citizens')" />
        <x-stat-tile :value="number_format($reportsThisWeek)" :label="__('admin.overview.reports_week')" />
        <x-stat-tile :value="number_format($openClusters)" :label="__('admin.overview.open_clusters')" />
        <x-stat-tile :value="number_format($unprocessed)" :label="__('admin.overview.unprocessed')" />
    </div>

    <section class="stack">
        <div class="section-title">
            <h2>{{ __('leader.reports.title') }}</h2>
            <a href="{{ route('admin.reports') }}">{{ __('citizen.home.see_all') }}</a>
        </div>
        @if ($latestReports->isEmpty())
            <div class="card"><p class="muted">{{ __('leader.reports.empty') }}</p></div>
        @else
            @include('partials.report-list', ['reports' => $latestReports, 'issueRoute' => 'clusters.show'])
        @endif
    </section>

    <section class="card stack">
        <h2>{{ __('admin.overview.spend_heading') }}</h2>
        <p>{{ __('admin.overview.spend_line', ['spent' => number_format($spent, 2), 'cap' => number_format($cap, 2)]) }}</p>
        @if ($spent >= $cap)
            <div class="notice notice-warn">{{ __('admin.overview.cap_reached') }}</div>
        @endif
        <p><a href="{{ route('admin.ai-usage') }}">{{ __('admin.overview.spend_link') }}</a></p>
    </section>

    @if ($unprocessed > 0)
        <div class="notice notice-warn">
            {{ trans_choice('admin.overview.unprocessed_notice', $unprocessed, ['count' => $unprocessed]) }}
            <a href="{{ route('admin.intake') }}">{{ __('admin.overview.unprocessed_link') }}</a>
        </div>
    @endif
</x-layout.staff>
