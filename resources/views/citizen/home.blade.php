<x-layout.citizen :title="__('citizen.raise.title')" active="home">
    <section class="card stack">
        <div>
            <h1>{{ __('citizen.raise.title') }}</h1>
            <p class="muted">{{ __('citizen.raise.intro', ['ward' => $citizen->ward->name]) }}</p>
        </div>
        @include('citizen.partials.raise-form')
    </section>

    <section>
        <div class="section-title">
            <h2>{{ __('citizen.home.top_issues', ['ward' => $citizen->ward->name]) }}</h2>
            <a href="{{ route('citizen.ward') }}">{{ __('citizen.home.see_all') }}</a>
        </div>
        @if ($clusters->isEmpty())
            <p class="muted">{{ __('citizen.ward.empty') }}</p>
        @else
            <p class="muted small">{{ __('citizen.ward.voice_explainer') }}</p>
            <ul class="issue-list">
                @foreach ($clusters as $i => $cluster)
                    <x-issue-card :cluster="$cluster" :rank="$i + 1" :citizen="$citizen"
                                  :reported="$state->reported($cluster)" :rated="$state->rated($cluster)" />
                @endforeach
            </ul>
        @endif
    </section>
</x-layout.citizen>
