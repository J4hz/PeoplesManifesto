<x-layout.citizen :title="__('public.ward.title', ['ward' => $ward->name])">
    <div class="page-head">
        <h1>{{ __('public.ward.title', ['ward' => $ward->name]) }}</h1>
        <p>{{ __('public.ward.subtitle', ['constituency' => $ward->constituency->name, 'county' => $ward->constituency->county->name]) }}</p>
    </div>

    @guest('citizen')
        <div class="card row row-between">
            <span>{{ __('public.ward.live_here') }}</span>
            <a class="btn" href="{{ route('citizen.sign-in') }}">{{ __('citizen.welcome.title') }}</a>
        </div>
    @endguest

    <section>
        <h2>{{ __('citizen.ward.open_heading') }}</h2>
        @if ($open->isEmpty())
            <p class="muted">{{ __('public.ward.empty') }}</p>
        @else
            <ul class="issue-list">
                @foreach ($open as $i => $cluster)
                    <x-issue-card :cluster="$cluster" :rank="$open->firstItem() + $i" />
                @endforeach
            </ul>
            {{ $open->links('partials.pagination') }}
        @endif
    </section>

    @if ($done->isNotEmpty())
        <section>
            <h2>{{ __('citizen.ward.done_heading') }}</h2>
            <ul class="issue-list">
                @foreach ($done as $cluster)
                    <x-issue-card :cluster="$cluster" />
                @endforeach
            </ul>
        </section>
    @endif

    @if ($leaders->isNotEmpty())
        <section class="card stack">
            <h2>{{ __('public.ward.leaders_heading') }}</h2>
            <ul class="timeline">
                @foreach ($leaders as $leader)
                    <li><a href="{{ route('leaders.show', $leader) }}">{{ $leader->name }}</a> <span class="muted">{{ $leader->office->name }}</span></li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layout.citizen>
