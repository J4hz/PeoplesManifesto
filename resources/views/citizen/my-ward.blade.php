<x-layout.citizen :title="__('citizen.ward.title', ['ward' => $citizen->ward->name])" active="ward">
    <div class="page-head">
        <h1>{{ __('citizen.ward.title', ['ward' => $citizen->ward->name]) }}</h1>
        <p>{{ __('citizen.ward.intro') }}</p>
    </div>

    <section>
        <h2>{{ __('citizen.ward.open_heading') }}</h2>
        @if ($open->isEmpty())
            <p class="muted">{{ __('citizen.ward.empty') }}</p>
        @else
            <p class="muted small">{{ __('citizen.ward.voice_explainer') }}</p>
            <ul class="issue-list">
                @foreach ($open as $i => $cluster)
                    <x-issue-card :cluster="$cluster" :rank="$open->firstItem() + $i" :citizen="$citizen"
                                  :reported="$state->reported($cluster)" :rated="$state->rated($cluster)" />
                @endforeach
            </ul>
            {{ $open->links('partials.pagination') }}
        @endif
    </section>

    @if ($done->isNotEmpty())
        <section>
            <h2>{{ __('citizen.ward.done_heading') }}</h2>
            <p class="muted small">{{ __('citizen.ward.done_explainer') }}</p>
            <ul class="issue-list">
                @foreach ($done as $cluster)
                    <x-issue-card :cluster="$cluster" :citizen="$citizen"
                                  :reported="$state->reported($cluster)" :rated="$state->rated($cluster)" />
                @endforeach
            </ul>
        </section>
    @endif

    <p class="small"><a href="{{ route('citizen.ward.choose') }}">{{ __('citizen.ward.change') }}</a></p>
</x-layout.citizen>
