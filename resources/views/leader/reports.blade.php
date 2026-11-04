<x-layout.staff :title="__('leader.reports.title')" :active="$active">
    <div class="page-head">
        <h1>{{ __('leader.reports.title') }}</h1>
        <p>{{ __('leader.reports.intro') }}</p>
    </div>

    @if ($wards->count() > 1)
        <form method="GET" action="{{ $filterRoute }}" class="row">
            <label for="ward" class="visually-hidden">{{ __('leader.overview.ward_filter') }}</label>
            <select id="ward" name="ward">
                <option value="">{{ __('leader.overview.all_wards') }}</option>
                @foreach ($wards as $ward)
                    <option value="{{ $ward->id }}" @selected($wardFilter === $ward->id)>{{ $ward->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-quiet">{{ __('leader.overview.filter') }}</button>
        </form>
    @endif

    @if ($reports->isEmpty())
        <div class="card"><p class="muted">{{ __('leader.reports.empty') }}</p></div>
    @else
        @include('partials.report-list', ['reports' => $reports, 'issueRoute' => $issueRoute])
        {{ $reports->links('partials.pagination') }}
    @endif
</x-layout.staff>
